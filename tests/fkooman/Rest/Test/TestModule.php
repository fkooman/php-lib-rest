<?php

namespace fkooman\Rest\Test;

use fkooman\Http\Request;
use fkooman\Rest\Service;
use fkooman\Rest\ServiceModuleInterface;

class TestModule implements ServiceModuleInterface
{
    /** @var string */
    private $routeName;

    public function __construct($routeName)
    {
        $this->routeName = $routeName;
    }

    public function init(Service $service)
    {
        $service->get(
            sprintf('/%s', $this->routeName),
            function (Request $request) {
                return $this->routeName;
            }
        );
    }
}
