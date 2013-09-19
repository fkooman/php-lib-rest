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
// Code 404, {"error":"not_found","error_description":"resource not found"}
// curl http://localhost/php-lib-rest/examples/rest.php/foo
//
// Code 405, {"error":"method_not_allowed","error_description":"request method not allowed"}
// curl -X DELETE http://localhost/php-lib-rest/examples/rest.php/hello/world
//
// Code 500, {"error":"internal_server_error","error_description":"you cannot say 'foo'!'"}
// curl -X POST http://localhost/php-lib-rest/examples/rest.php/hello/foo
//

require_once dirname(__DIR__) . '/vendor/autoload.php';

use fkooman\Http\Service;
use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\IncomingRequest;

try {
    $service = new Service();

    $service->match(
        "GET",
        "/hello/:str",
        function ($str) {
            $response = new Response(200, "application/json");
            $response->setContent(
                json_encode(
                    array(
                        "type" => "GET",
                        "response" => sprintf("hello %s", $str)
                    )
                )
            );

            return $response;
        }
    );

    $service->match(
        "POST",
        "/hello/:str",
        function ($str) {
            if ("foo" === $str) {
                // it would make more sense to create something like an ApiException
                // class that would return the code 400 "Bad Request" instead of
                // internal server error as this is a 'mistake' by the client...
                throw new Exception("you cannot say 'foo'!'");
            }
            $response = new Response(200, "application/json");
            $response->setContent(
                json_encode(
                    array(
                        "type" => "POST",
                        "response" => sprintf("hello %s", $str)
                    )
                )
            );

            return $response;
        }
    );

    $service->run(
        Request::fromIncomingRequest(
            new IncomingRequest()
        )
    )->sendResponse();
} catch (Exception $e) {
    $response = new Response(500, "application/json");
    $response->setContent(
        json_encode(
            array(
                "error" => "internal_server_error",
                "error_description" => $e->getMessage()
            )
        )
    );
    $response->sendResponse();
}
