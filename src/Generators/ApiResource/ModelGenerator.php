<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class ModelGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates the Eloquent model class (fillable, casts, relationships, searchable, etc.).';
    }

    public function handle()
    {
        $source = $this->generateSource();
        $resource = $this->resource();

        $path = app_path('Models') . '/' . $resource->name()->singular()->studly() . ".php";
        $this->createFile($path, $source);

        // Only flag unset fields the user is likely to want — silence the rest.
        $missing = [];
        foreach (['fillable', 'casts', 'sortable', 'filterable', 'searchable'] as $key) {
            if (!$resource->has($key) && !$resource->has("model.{$key}")) {
                $missing[] = '$' . $key;
            }
        }
        if (!empty($missing)) {
            $this->logger()?->info("  hint: model has no " . implode(', ', $missing) . " — defaults apply");
        }
    }

    public function generateSource()
    {
        $resource = $this->resource();

        return $this->render(
            'api-resource/model',
            [
                'resource' => $resource,
            ]
        );        
    }
}