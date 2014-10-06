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

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\JsonResponse;
use fkooman\Http\IncomingRequest;
use fkooman\Rest\Plugin\BasicAuthentication;
use fkooman\Http\Exception\HttpException;
use fkooman\Http\Exception\BadRequestException;

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
            $response = new JsonResponse(200);
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
                // it would make more sense to create something like an ApiException
                // class that would return the code 400 "Bad Request" instead of
                // internal server error as this is a 'mistake' by the client...
                throw new BadRequestException('you cannot say "foo!"');
            }
            $response = new JsonResponse(200);
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
    $message = $e->getMessage();
    if ($e instanceof HttpException) {
        $code = $e->getCode();
        $reason = $e->getReason();
    } else {
        $code = 500;
        $reason = 'Internal Server Error';
    }
    $response = new JsonResponse($code);
    $response->setContent(
        array(
            'error' => $reason,
            'error_description' => $message,
        )
    );
    $response->sendResponse();
}
