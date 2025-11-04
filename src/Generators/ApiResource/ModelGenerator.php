<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class ModelGenerator extends BaseGenerator
{
    public function handle()
    {
        $source = $this->generateSource();
        $resource = $this->resource();

        $path = app_path('Models') . '/' . $resource->name()->singular()->studly() . ".php";
        $this->createFile($path, $source);
        $this->logger()->info("# Model created: {$path}");
        $this->logger()->info('- [Optional] Set model $fillable (all fields allowed by default)');
        $this->logger()->info('- [Optional] Set model $casts (no casting by default)');
        $this->logger()->info('  - Allowed mutators https://laravel.com/docs/eloquent-mutators#attribute-casting');
        $this->logger()->info('- [Optional] Set model $sortable (all fields allowed by default)');
        $this->logger()->info('- [Optional] Set model $filterable (allowed filters "id", "string", "integer", "date", "datetime")');
        $this->logger()->info('- [Optional] Set model $searchable (defines fields for search indexing via toSearchableArray())');
        $this->logger()->info('- [Optional] Set relationships');
        $this->logger()->info('  - Allowed relationships https://laravel.com/docs/eloquent-relationships');
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