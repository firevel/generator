<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class MigrationGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();
        $name = date('Y_m_d_His') . "_create_{$resource->name()->plural()->snake()}_table";

        $source = $this->render(
            'api-resource/migration',
            [
                'resource' => $resource,
            ]
        );


        $path = database_path('migrations' . '/' . "{$name}.php");

        $this->createFile($path, $source);

        $this->logger()->info("# Migration created: {$name}");
        $this->logger()->info('- [Required] Set migration');
        $this->logger()->info('  - Available column types https://laravel.com/docs/migrations#available-column-types)');
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