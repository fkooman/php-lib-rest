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

use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\IncomingRequest;

$request = null;
$response = null;

try {
    $request = Request::fromIncomingRequest(new IncomingRequest());

    $request->matchRest("GET", "/hello/:str", function($str) use (&$response) {
        $response = new Response(200, "application/json");
        $response->setContent(json_encode(array("type" => "GET", "response" => "hello " . $str)));
    });

    $request->matchRest("POST", "/hello/:str", function($str) use (&$response) {
        if ("foo" === $str) {
            // it would make more sense to create something like an ApiException
            // class that would return the code 400 "Bad Request" instead of
            // internal server error as this is a 'mistake' by the client...
            throw new Exception("you cannot say 'foo'!'");
        }
        $response = new Response(200, "application/json");
        $response->setContent(json_encode(array("type" => "POST", "response" => "hello " . $str)));
    });

    $request->matchRestDefault(function($methodMatch, $patternMatch) use ($request, &$response) {
        // methodMatch contains all the used request methods 'registrered'
        // through the matchRest method calls above, in this case GET and POST
        //
        // patternMatch indicates (boolean) whether or not the request URL
        // matches any of the patterns 'registered' through the matchRest
        // methods above...
       if (!in_array($request->getRequestMethod(), $methodMatch)) {
            $response = new Response(405, "application/json");
            $response->setHeader("Allow", implode(",", $methodMatch));
            $response->setContent(json_encode(array("error" => "method_not_allowed", "error_description" => "request method not allowed")));
        } elseif (!$patternMatch) {
            $response = new Response(404, "application/json");
            $response->setContent(json_encode(array("error" => "not_found", "error_description" => "resource not found")));
        }
    });

} catch (Exception $e) {
    $response = new Response(500, "application/json");
    $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
}

$response->sendResponse();
