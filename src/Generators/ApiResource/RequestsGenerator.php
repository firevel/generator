<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class RequestsGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates FormRequest classes (Index/Store/Show/Update/Destroy) for the resource controller.';
    }

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
        }

        // Emit the hint once for the whole group instead of once per action —
        // the rules() body looks the same across all five generated requests.
        $this->logger()?->info("  hint: rules() in generated requests is empty — populate per action");
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