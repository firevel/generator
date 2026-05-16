<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class MigrationsGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates database migrations for the resource table and any pivot tables.';
    }

    public function handle()
    {
        $this->generateResourceMigration();
        $this->generatePivotMigrations();
    }

    protected function generateResourceMigration()
    {
        $resource = $this->resource();
        $tableName = $resource->name()->plural()->snake();
        $migrationPattern = "*_create_{$tableName}_table.php";
        $migrationsPath = database_path('migrations');

        // Check for existing migrations matching the pattern
        $existingMigrations = glob($migrationsPath . '/' . $migrationPattern);

        if (!empty($existingMigrations)) {
            $existingFilePath = $existingMigrations[0];

            $shouldOverride = true;
            if (method_exists($this->logger(), 'confirm')) {
                $shouldOverride = $this->logger()->confirm(
                    "Migration for '{$tableName}' table already exists. Overwrite?",
                    true
                );
            }

            if (!$shouldOverride) {
                $this->logger()->info("skipped " . $this->relativePath($existingFilePath) . " (kept existing)");
                $this->recordFileAction('skipped', $existingFilePath);
                return;
            }

            $source = $this->render('api-resource/migration', ['resource' => $resource]);
            $this->createFile($existingFilePath, $source);
        } else {
            $name = date('Y_m_d_His') . "_create_{$tableName}_table";
            $path = database_path('migrations' . '/' . "{$name}.php");

            $source = $this->render('api-resource/migration', ['resource' => $resource]);
            $this->createFile($path, $source);
        }

        if (!$resource->has('migrations.create')) {
            $this->addManualStep("define columns for '{$tableName}' migration (schema has no migrations.create)");
        }
    }

    protected function generatePivotMigrations()
    {
        $resource = $this->resource();

        if (!$resource->has('migrations.pivot')) {
            return;
        }

        $pivots = $resource->get('migrations.pivot');
        if (!is_array($pivots) || empty($pivots)) {
            return;
        }

        $migrationsPath = database_path('migrations');
        $emitted = $this->context->get('emitted_pivots', []);

        foreach ($pivots as $pivot) {
            if (empty($pivot['table']) || empty($pivot['fields'])) {
                continue;
            }

            $table = $pivot['table'];

            // Within-run dedupe (shared across iterated resources via PipelineContext).
            if (in_array($table, $emitted, true)) {
                continue;
            }

            // Cross-run dedupe: skip if a pivot migration for this table already exists.
            $existing = glob($migrationsPath . '/*_create_' . $table . '_pivot_table.php');
            if (!empty($existing)) {
                $this->logger()->info("skipped " . $this->relativePath($existing[0]) . " (pivot exists)");
                $this->recordFileAction('skipped', $existing[0]);
                $emitted[] = $table;
                $this->context->set('emitted_pivots', $emitted);
                continue;
            }

            $name = date('Y_m_d_His') . "_create_{$table}_pivot_table";
            $path = $migrationsPath . '/' . "{$name}.php";

            $source = $this->render('api-resource/pivot-migration', ['pivot' => $pivot]);

            $this->createFile($path, $source);

            $emitted[] = $table;
            $this->context->set('emitted_pivots', $emitted);
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