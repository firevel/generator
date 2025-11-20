<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class MigrationsGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();
        $tableName = $resource->name()->plural()->snake();
        $migrationPattern = "*_create_{$tableName}_table.php";
        $migrationsPath = database_path('migrations');

        // Check for existing migrations matching the pattern
        $existingMigrations = glob($migrationsPath . '/' . $migrationPattern);

        if (!empty($existingMigrations)) {
            // Use the first matching migration file
            $existingFilePath = $existingMigrations[0];
            $existingFileName = basename($existingFilePath);
            $this->logger()->info("Found existing migration: {$existingFileName}");

            // Check if logger supports interactive confirmation
            $shouldOverride = true; // default to yes
            if (method_exists($this->logger(), 'confirm')) {
                $shouldOverride = $this->logger()->confirm(
                    "A migration for {$tableName} table already exists. Do you want to override it?",
                    true // default to yes
                );
            }

            if (!$shouldOverride) {
                $this->logger()->info("Skipped migration creation for {$tableName} table");
                return;
            }

            // Overwrite the existing migration file
            $source = $this->render(
                'api-resource/migration',
                [
                    'resource' => $resource,
                ]
            );

            $this->createFile($existingFilePath, $source);

            $this->logger()->info("# Migration overwritten: {$existingFileName}");
            $this->logger()->info('- [Required] Set migration');
            $this->logger()->info('  - Available column types https://laravel.com/docs/migrations#available-column-types)');
        } else {
            // No existing migration, create a new one
            $name = date('Y_m_d_His') . "_create_{$tableName}_table";
            $path = database_path('migrations' . '/' . "{$name}.php");

            $source = $this->render(
                'api-resource/migration',
                [
                    'resource' => $resource,
                ]
            );

            $this->createFile($path, $source);

            $this->logger()->info("# Migration created: {$name}");
            $this->logger()->info('- [Required] Set migration');
            $this->logger()->info('  - Available column types https://laravel.com/docs/migrations#available-column-types)');
        }
    }

    function generateMigrationCode($chains) {
        $lines = [];

        foreach ($chains as $chainMethods) {
            $line = "\$table";

            foreach ($chainMethods as $method) {
                $methodName = $method['name'];

                $params = [];
                if (isset($method['params'])) {
                    foreach ($method['params'] as $param) {
                        if (is_string($param)) {
                            $params[] = "'$param'";
                        } elseif (is_array($param)) {
                            $arrayValues = array_map(function ($value) {
                                return "'$value'";
                            }, $param);
                            $params[] = "[" . implode(', ', $arrayValues) . "]";
                        } else {
                            $params[] = $param;
                        }
                    }
                }

                $paramString = implode(', ', $params);
                $line .= "->{$methodName}({$paramString})";
            }

            $line .= ";";
            $lines[] = $line;
        }

        return $lines;
    }



}