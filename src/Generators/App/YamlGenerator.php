<?php

namespace Firevel\Generator\Generators\App;

use Firevel\Generator\Generators\BaseGenerator;
use Firevel\Generator\Resource;

class YamlGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();

        if ($resource->empty()) {
            $projectType = $this->logger()->choice(
                'Does your project consist of multiple services or just one?',
                ['Multiple services', 'One service'],
                'One service'
            );

            if ($projectType == 'One service') {
                $resource->name = 'default';
            }

            if ($projectType == 'Multiple services') {
                $resource->name = $this->logger()->ask('Whats the service name (leave emtpy for default)?', 'default');
            }

            $resource->runtime = $this->logger()->choice(
                'What runtime would you like to use?',
                ['php81', 'php82', 'php83', 'php84'],
                'php84'
            );
        }

        $file = $this->render(
            'app-yaml',
            [
                'resource' => $resource,
            ]
        );

        $this->createFile('app.yaml', $file);
        $this->logger()->info("app.yaml file generated");
    }
}
