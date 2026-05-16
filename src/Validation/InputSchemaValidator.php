<?php

namespace Firevel\Generator\Validation;

use JsonSchema\Validator as JsonSchemaValidator;

/**
 * Validates pipeline input against a JSON Schema.
 *
 * Schemas live under the `input_schema` key of a hybrid pipeline definition.
 * `input_schema` may be:
 *   - an inline associative array (the schema itself, as PHP)
 *   - a path to a `.json` file on disk
 *
 * Custom user-facing messages can be provided in parallel via `input_error_messages`,
 * keyed by JSON Pointer to the failing property (e.g. `/resources` or `/service/runtime`).
 * The empty string `''` matches root-level failures (the `required` keyword reports
 * errors at the parent's pointer, which is `''` for top-level required fields).
 *
 *     'input_schema' => [
 *         'type' => 'object',
 *         'required' => ['resources'],
 *         'not' => ['required' => ['service']],
 *     ],
 *     'input_error_messages' => [
 *         '/resources' => 'Pipeline ... requires a `resources` array. ...',
 *         '/service'   => 'Pipeline ... does not accept `service` ...',
 *     ]
 */
class InputSchemaValidator
{
    /**
     * @param array       $attributes the resolved pipeline input
     * @param array|string $schema either an inline schema (assoc array) or a .json file path
     * @param array<string,string> $errorMessages json-pointer => custom message
     * @return string[] human-readable error strings (empty on success)
     */
    public static function validate(array $attributes, $schema, array $errorMessages, string $pipelineName): array
    {
        $schemaObject = self::loadSchema($schema, $pipelineName);

        // justinrainbow expects PHP objects, not associative arrays. An empty
        // array would round-trip through json as `[]` (still an array) — force
        // top-level to stdClass so `type: object` schemas don't trip on it.
        if (empty($attributes)) {
            $payload = new \stdClass();
        } else {
            $payload = json_decode(json_encode($attributes));
        }

        $validator = new JsonSchemaValidator();
        $validator->validate($payload, $schemaObject);

        if ($validator->isValid()) {
            return [];
        }

        $errors = [];
        foreach ($validator->getErrors() as $error) {
            $errors[] = self::formatError($error, $errorMessages, $pipelineName);
        }

        return array_values(array_unique($errors));
    }

    /**
     * Load schema from array or file path. Returns the decoded JSON object.
     */
    protected static function loadSchema($schema, string $pipelineName)
    {
        if (is_array($schema)) {
            return json_decode(json_encode($schema));
        }

        if (is_string($schema)) {
            if (!is_file($schema)) {
                throw new \RuntimeException(
                    "Pipeline '{$pipelineName}' input_schema points at '{$schema}' but the file does not exist."
                );
            }
            $raw = file_get_contents($schema);
            $decoded = json_decode($raw);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException(
                    "Pipeline '{$pipelineName}' input_schema at '{$schema}' is not valid JSON: " . json_last_error_msg()
                );
            }
            return $decoded;
        }

        throw new \RuntimeException(
            "Pipeline '{$pipelineName}' input_schema must be an array or file path, got " . gettype($schema)
        );
    }

    /**
     * Format a single validator error. Prefers the user's custom message when
     * a matching JSON Pointer is registered.
     *
     * For "required" keyword failures, the validator reports the error at the
     * *parent's* pointer with the missing property name in the message. We
     * also look up `parentPointer/propertyName` so users can attach messages
     * to the missing path directly.
     */
    protected static function formatError(array $error, array $errorMessages, string $pipelineName): string
    {
        $pointer = '/' . ltrim($error['pointer'] ?? '', '/');
        if ($pointer === '/') {
            $pointer = '';
        }

        // For required-keyword errors, try the more specific pointer of the
        // missing property (`<parent>/<property>`).
        if (($error['constraint']['name'] ?? null) === 'required') {
            $missingProperty = $error['constraint']['params']['property'] ?? null;
            if ($missingProperty !== null) {
                $candidate = $pointer === '' ? "/{$missingProperty}" : "{$pointer}/{$missingProperty}";
                if (isset($errorMessages[$candidate])) {
                    return $errorMessages[$candidate];
                }
            }
        }

        if (isset($errorMessages[$pointer])) {
            return $errorMessages[$pointer];
        }

        // Generic fallback. Include path so the user knows where to look.
        $message = $error['message'] ?? 'schema validation failed';
        $location = $pointer === '' ? '(root)' : $pointer;
        return "Pipeline '{$pipelineName}' input failed at {$location}: {$message}";
    }
}
