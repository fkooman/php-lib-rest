<?php

namespace fkooman\Rest\Plugin;

use fkooman\Rest\ServicePluginInterface;
use fkooman\Http\Request;
use fkooman\Rest\Service;

class TestPlugin implements ServicePluginInterface
{
    public function init(Service $service)
    {
        $service->get(
            '/foo',
            function () {
                return 'foo';
            }
        );
    }

    public function execute(Request $request, array $routeConfig)
    {
        return;
    }
}
