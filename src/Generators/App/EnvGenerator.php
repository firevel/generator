<?php

namespace Firevel\Generator\Generators\App;

use Firevel\Generator\Generators\BaseGenerator;

class EnvGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();

        // Get the full input to access top-level "env" field
        $input = $this->input();

        // Check if env field exists, if not skip silently
        if ($input && $input->has('env')) {
            $envVars = $input->get('env', []);
        } elseif ($resource->has('env')) {
            $envVars = $resource->get('env', []);
        } else {
            // No env field - skip silently since it's optional
            return;
        }

        if (empty($envVars) || !is_array($envVars)) {
            return;
        }

        $envPath = base_path('.env');

        // Read existing .env file if it exists
        $existingEnv = [];
        $envLines = [];

        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $envLines = explode("\n", $envContent);

            // Parse existing env variables
            foreach ($envLines as $line) {
                // Skip comments and empty lines
                if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse KEY=VALUE format
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $existingEnv[trim($key)] = trim($value);
                }
            }
        }

        $varsToAdd = [];
        $varsToUpdate = [];

        // Check which variables already exist
        foreach ($envVars as $key => $value) {
            if (isset($existingEnv[$key])) {
                $varsToUpdate[$key] = [
                    'old' => $existingEnv[$key],
                    'new' => $value,
                ];
            } else {
                $varsToAdd[$key] = $value;
            }
        }

        // Ask for confirmation on existing variables
        $confirmedUpdates = [];
        foreach ($varsToUpdate as $key => $values) {
            if ($values['old'] === $values['new']) {
                $this->logger()->info("Environment variable {$key} already exists with the same value");
                continue;
            }

            $override = $this->logger()->confirm(
                "Environment variable {$key} already exists (current: {$values['old']}, new: {$values['new']}). Override?",
                true
            );

            if ($override) {
                $confirmedUpdates[$key] = $values['new'];
            }
        }

        // Merge new variables and variables to update
        $allVarsToProcess = array_merge($varsToAdd, $confirmedUpdates);

        if (empty($allVarsToProcess)) {
            $this->logger()->info("No changes to .env file");
            return;
        }

        // Update the .env file
        if (file_exists($envPath)) {
            // Update existing file
            $updatedLines = [];
            $processedKeys = [];

            foreach ($envLines as $line) {
                $trimmedLine = trim($line);

                // Keep comments and empty lines as is
                if (empty($trimmedLine) || strpos($trimmedLine, '#') === 0) {
                    $updatedLines[] = $line;
                    continue;
                }

                // Check if this line contains a variable we're updating
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);

                    if (isset($allVarsToProcess[$key])) {
                        // Update the value
                        $newValue = $this->formatEnvValue($allVarsToProcess[$key]);
                        $updatedLines[] = "{$key}={$newValue}";
                        $processedKeys[] = $key;

                        $action = isset($confirmedUpdates[$key]) ? 'Updated' : 'Added';
                        $this->logger()->info("{$action} {$key}={$newValue}");
                    } else {
                        // Keep the line as is
                        $updatedLines[] = $line;
                    }
                } else {
                    $updatedLines[] = $line;
                }
            }

            // Add new variables that weren't in the file
            foreach ($allVarsToProcess as $key => $value) {
                if (!in_array($key, $processedKeys)) {
                    $formattedValue = $this->formatEnvValue($value);
                    $updatedLines[] = "{$key}={$formattedValue}";
                    $this->logger()->info("Added {$key}={$formattedValue}");
                }
            }

            $content = implode("\n", $updatedLines);
        } else {
            // Create new .env file
            $lines = [];
            foreach ($allVarsToProcess as $key => $value) {
                $formattedValue = $this->formatEnvValue($value);
                $lines[] = "{$key}={$formattedValue}";
                $this->logger()->info("Added {$key}={$formattedValue}");
            }
            $content = implode("\n", $lines) . "\n";
        }

        file_put_contents($envPath, $content);
        $this->logger()->info(".env file updated successfully");
    }

    /**
     * Format the value for .env file
     * Wraps in quotes if it contains spaces or special characters
     *
     * @param mixed $value
     * @return string
     */
    protected function formatEnvValue($value)
    {
        // Convert boolean values
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        // Convert null to empty string
        if ($value === null) {
            return '';
        }

        // Convert to string
        $value = (string) $value;

        // Wrap in quotes if it contains spaces or special characters
        if (preg_match('/[\s#]/', $value)) {
            // Escape existing quotes
            $value = str_replace('"', '\\"', $value);
            return "\"{$value}\"";
        }

        return $value;
    }
}
