<?php

// This application will show how to use the REST service routing capabilies
// of this framework
//
// You can use cURL to test this code, some example commands, assuming the
// code is available through
// http://localhost/php-lib-rest/examples/rest.php:
//
// Code 200, {"type":"GET","response":"hello world"}:
// curl http://localhost/php-lib-rest/examples/rest.php/hello/world
//
// Code 404, {"code":404,"error":"Not Found"}
// curl http://localhost/php-lib-rest/examples/rest.php/foo
//
// {"code":405,"error":"Method Not Allowed"}
// curl -X DELETE http://localhost/php-lib-rest/examples/rest.php/hello/world
//
// Code 400, {"error":"Bad Request","error_description":"you cannot say \"foo!\""}
// curl -X POST http://localhost/php-lib-rest/examples/rest.php/hello/foo
//

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Http\Request;
use fkooman\Http\JsonResponse;
use fkooman\Http\IncomingRequest;
use fkooman\Rest\Service;
use fkooman\Rest\Plugin\BasicAuthentication;
use fkooman\Http\Exception\HttpException;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\InternalServerErrorException;

try {
    $service = new Service(
        Request::fromIncomingRequest(
            new IncomingRequest()
        )
    );

    // require all requests to have valid authentication
    //$service->registerBeforeMatchingPlugin(
    //   new BasicAuthentication('foo', 'bar', 'My Secured Foo Service')
    //);

    $service->get(
        '/hello/:str',
        function ($str) {
            $response = new JsonResponse();
            $response->setContent(
                array(
                    'type' => 'GET',
                    'response' => sprintf('hello %s', $str),
                )
            );

            return $response;
        }
    );

    $service->post(
        '/hello/:str',
        function ($str) {
            if ('foo' === $str) {
                throw new BadRequestException('you cannot say "foo!"');
            }
            $response = new JsonResponse();
            $response->setContent(
                array(
                    'type' => 'POST',
                    'response' => sprintf('hello %s', $str),
                )
            );

            return $response;
        }
    );

    $service->run()->sendResponse();
} catch (Exception $e) {
    if ($e instanceof HttpException) {
        $response = $e->getResponse();
    } else {
        // we catch all other (unexpected) exceptions and return a 500
        $e = new InternalServerErrorException($e->getMessage());
        $response = $e->getResponse();
    }
    $response->sendResponse();
}
