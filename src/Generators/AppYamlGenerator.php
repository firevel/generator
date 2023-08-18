<?php

namespace Firevel\Generator\Generators;

use Firevel\Generator\Resource;

class AppYamlGenerator extends BaseGenerator
{
    public function generate()
    {
        $resource = $this->resource();
        $projectType = $this->logger()->choice(
            'Does your project consist of multiple services or just one?',
            ['One service', 'Multiple services'],
            'One service'
        );

        if ($projectType == 'One service') {
            $name = 'default';
        }

        if ($projectType == 'Multiple services') {
            $name = $this->logger()->ask('Whats the service name (leave emtpy for default)?', 'default');
        }

        $runtime = $this->logger()->choice(
            'What runtime you would like to use?',
            ['php81', 'php82'],
            'php82'
        );
        $resource->name = $name;
        $resource->setAttribute('runtime', $runtime);

        $file = $this->render(
            'app',
            [
                'name' => $resource->name,
                'runtime' => $resource->getAttribute('runtime')
            ]
        );

        $this->createFile('app.yaml', $file);
        $this->logger()->info("app.yaml file generated");
    }
}
