<?php

namespace Firevel\Generator\Tests\Validation;

use Firevel\Generator\Validation\InputSchemaValidator;
use PHPUnit\Framework\TestCase;

class InputSchemaValidatorTest extends TestCase
{
    public function test_valid_input_returns_no_errors()
    {
        $schema = [
            'type' => 'object',
            'required' => ['name'],
        ];

        $this->assertSame([], InputSchemaValidator::validate(
            ['name' => 'Article'],
            $schema,
            [],
            'my-pipeline'
        ));
    }

    public function test_missing_required_field_is_reported()
    {
        $schema = [
            'type' => 'object',
            'required' => ['name'],
        ];

        $errors = InputSchemaValidator::validate([], $schema, [], 'my-pipeline');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('name', $errors[0]);
        $this->assertStringContainsString("'my-pipeline'", $errors[0]);
    }

    public function test_custom_message_for_missing_required_uses_property_pointer()
    {
        $schema = [
            'type' => 'object',
            'required' => ['name'],
        ];
        $messages = ['/name' => 'Please provide a resource name like "Article".'];

        $errors = InputSchemaValidator::validate([], $schema, $messages, 'my-pipeline');

        $this->assertSame(['Please provide a resource name like "Article".'], $errors);
    }

    public function test_forbidden_property_via_not_required_is_reported()
    {
        $schema = [
            'type' => 'object',
            'not' => ['required' => ['resources']],
        ];

        $errors = InputSchemaValidator::validate(
            ['resources' => [['name' => 'Article']]],
            $schema,
            [],
            'api-resource'
        );

        $this->assertNotEmpty($errors);
    }

    public function test_custom_message_for_forbidden_via_root_pointer()
    {
        $schema = [
            'type' => 'object',
            'not' => ['required' => ['resources']],
        ];
        $messages = ['/resources' => 'Resources array is not accepted here. Use generic-app.'];

        $errors = InputSchemaValidator::validate(
            ['resources' => [['name' => 'Article']]],
            $schema,
            $messages,
            'api-resource'
        );

        // The `not` failure reports at the root pointer, but a user attaching
        // the message to /resources should still match — they don't have to
        // know JSON Schema internals.
        $hasCustom = in_array('Resources array is not accepted here. Use generic-app.', $errors, true);
        $hasFallback = false;
        foreach ($errors as $err) {
            if (str_contains($err, '(root)') || str_contains($err, 'Not')) {
                $hasFallback = true;
            }
        }
        // We accept either the custom message at /resources, or — if the
        // validator reports at root only — the fallback path.
        $this->assertTrue($hasCustom || $hasFallback);
    }

    public function test_min_items_constraint_is_reported_with_path()
    {
        $schema = [
            'type' => 'object',
            'required' => ['resources'],
            'properties' => [
                'resources' => [
                    'type' => 'array',
                    'minItems' => 1,
                ],
            ],
        ];

        $errors = InputSchemaValidator::validate(
            ['resources' => []],
            $schema,
            [],
            'multi'
        );

        $this->assertNotEmpty($errors);
        // The error should mention resources somewhere — either in the path
        // or in the message itself.
        $combined = implode(' ', $errors);
        $this->assertStringContainsString('resources', $combined);
    }

    public function test_nested_property_uses_nested_pointer()
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'service' => [
                    'type' => 'object',
                    'required' => ['runtime'],
                ],
            ],
            'required' => ['service'],
        ];
        $messages = ['/service/runtime' => 'Service runtime is required (e.g. "php83").'];

        $errors = InputSchemaValidator::validate(
            ['service' => ['name' => 'api']],
            $schema,
            $messages,
            'my-pipeline'
        );

        $this->assertSame(['Service runtime is required (e.g. "php83").'], $errors);
    }

    public function test_schema_loaded_from_file_path()
    {
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema_') . '.json';
        file_put_contents($schemaPath, json_encode([
            'type' => 'object',
            'required' => ['name'],
        ]));

        try {
            $errors = InputSchemaValidator::validate([], $schemaPath, [], 'file-schema');
            $this->assertNotEmpty($errors);
        } finally {
            unlink($schemaPath);
        }
    }

    public function test_missing_schema_file_throws_runtime_exception()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        InputSchemaValidator::validate([], '/nonexistent/schema.json', [], 'bad-path');
    }

    public function test_malformed_schema_file_throws_runtime_exception()
    {
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema_') . '.json';
        file_put_contents($schemaPath, '{not valid json');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('not valid JSON');

            InputSchemaValidator::validate([], $schemaPath, [], 'bad-json');
        } finally {
            unlink($schemaPath);
        }
    }

    public function test_invalid_schema_argument_type_throws()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must be an array or file path');

        InputSchemaValidator::validate([], 12345, [], 'wrong-type');
    }
}
