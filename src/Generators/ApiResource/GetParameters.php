<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\Generator\Generators\BaseGenerator;
use Firevel\Generator\Resource;

class GetParameters extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();

        if ($resource->empty()) {
            $resource->name = $this->logger()->ask('Whats the name of your resource? Use singular name starting with capital letter');

            if ($resource->name()->singular() == $resource->name()->plural()) {
                $this->logger()->error("{$name} is not countable and cant be used as resource name");
                return 1;
            }

            if (ctype_lower($resource->name[0])) {
                $this->logger()->error("The name of the resource must begin with a capital letter (ex.: User, UserActions)");
                return 1;
            }
        }
    }
}
