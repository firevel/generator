<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class RequestsGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();
        $actions = ['index', 'store', 'show', 'update', 'destroy'];

        foreach ($actions as $action) {
            $source = $this->generateSource($action, $resource);

            $filename = $resource->name()->singular()->studly();
            if ($action == 'index') {
                $filename = $resource->name()->plural()->studly();
            }
            $path = app_path("Http/Requests/Api/" . $resource->name()->singular()->studly()) . '/' . ucfirst($action). "$filename.php";
            $this->createFile($path, $source);
            $this->logger()->info("# Request created: {$path}");
            $this->logger()->info('- [Required] Set rules');
            $this->logger()->info('  - Validation rules https://laravel.com/docs/validation#available-validation-rules');
            $this->logger()->info('- [Optional] Set authorize() if validation is based on request content.');
        }
   }

    public function generateSource($action, $resource)
    {
        return $this->render(
            'api-resource/requests/' . $action,
            [
                'resource' => $resource,
            ]
        );        
    }
}