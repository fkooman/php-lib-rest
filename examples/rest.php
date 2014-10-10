<?php

// This application will show how to use the REST service routing capabilies
// of this framework
//
// You can use cURL to test this code, some example commands, assuming the
// code is available through
// http://localhost/php-lib-rest/examples/rest.php:
//
// Code 200, {"type":"GET","response":"hello world"}:
// curl -i http://localhost/php-lib-rest/examples/rest.php/hello/world
//
// Code 404, {"code":404,"error":"Not Found"}
// curl -i http://localhost/php-lib-rest/examples/rest.php/foo
//
// {"code":405,"error":"Method Not Allowed"}
// curl -i -X DELETE http://localhost/php-lib-rest/examples/rest.php/hello/world
//
// Code 400, {"error":"Bad Request","error_description":"you cannot say \"foo!\""}
// curl -i -X POST http://localhost/php-lib-rest/examples/rest.php/hello/foo

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
    $service = new Service();

    // require all requests to have valid authentication
    //$u = 'foo';
    // NOTE: password is generated using the "password_hash()" function from
    // PHP 5.6 or the ircmaxell/password-compat library. This way no plain
    // text passwords are stored anywhere, below is the hashed value of 'bar'
    //$p = '$2y$10$ARD9Oq9xCzFANYGhv0mWxOsOallAS3qLQxLoOtzzRuLhv0U1IU9EO';

    //$service->registerBeforeMatchingPlugin(
    //   new BasicAuthentication($u, $p, 'My Secured Foo Service')
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

    $request = Request::fromIncomingRequest(
        new IncomingRequest()
    );
    $service->run($request)->sendResponse();
} catch (Exception $e) {
    if ($e instanceof HttpException) {
        $response = $e->getJsonResponse();
    } else {
        // we catch all other (unexpected) exceptions and return a 500
        $e = new InternalServerErrorException($e->getMessage());
        $response = $e->getJsonResponse();
    }
    $response->sendResponse();
}
