<?php

return [
   /*
    * Pipelines used for file generation.
    */
   'pipelines' => [
      'app' => [
          'yaml' => \Firevel\Generator\Generators\AppYamlGenerator::class,
      ]
   ],
];
