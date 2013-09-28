<?php

namespace fkooman\Http;

use UnexpectedValueException;

class Service
{
    /** @var fkooman\Http\Request */
    private $request;

    /** @var array */
    private $match;

    /** @var array */
    private $supportedMethods;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->match = array();
        $this->supportedMethods = array();
    }

    public function match($requestMethod, $requestPattern, $callback)
    {
        $this->match[] = array(
            "requestMethod" => $requestMethod,
            "requestPattern" => $requestPattern,
            "callback" => $callback
        );
        if (!in_array($requestMethod, $this->supportedMethods)) {
            $this->supportedMethods[] = $requestMethod;
        }
    }

    public function run()
    {
        foreach ($this->match as $m) {
            $response = $this->matchRest(
                $m['requestMethod'],
                $m['requestPattern'],
                $m['callback']
            );

            // false indicates not a match
            if (false !== $response) {
                if ($response instanceof Response) {
                    return $response;
                }
                if (!is_string($response)) {
                    throw new UnexpectedValueException("callback MUST return Response object or string");
                }
                $responseObj = new Response(200, "text/html");
                $responseObj->setContent($response);

                return $responseObj;
            }
        }

        // handle non matching patterns
        if (in_array($this->request->getRequestMethod(), $this->supportedMethods)) {
            $response = new JsonResponse(404);
            $response->setContent(
                array(
                    "code" => 404,
                    "error" => "Not Found"
                )
            );

            return $response;
        }

        $response = new JsonResponse(405);
        $response->setHeader("Allow", implode(",", $this->supportedMethods));
        $response->setContent(
            array(
                "code" => 405,
                "error" => "Method Not Allowed"
            )
        );

        return $response;
    }

    private function matchRest($requestMethod, $requestPattern, $callback)
    {
        if ($requestMethod !== $this->request->getRequestMethod()) {
            return false;
        }
        // if no pattern is defined, all paths are valid
        if (null === $requestPattern) {
            return call_user_func_array($callback, array());
        }
        // both the pattern and request path should start with a "/"
        if (0 !== strpos($this->request->getPathInfo(), "/") || 0 !== strpos($requestPattern, "/")) {
            return false;
        }

        // handle optional parameters
        $requestPattern = str_replace(')', ')?', $requestPattern);

        // check for variables in the requestPattern
        $pma = preg_match_all('#:([\w]+)\+?#', $requestPattern, $matches);
        if (false === $pma) {
            throw new RequestException("regex for variable search failed");
        }
        if (0 === $pma) {
            // no variables in the pattern, pattern and request must be identical
            if ($this->request->getPathInfo() === $requestPattern) {
                return call_user_func_array($callback, array());
            }
            // FIXME?!
            //return false;
        }
        // replace all the variables with a regex so the actual value in the request
        // can be captured
        foreach ($matches[0] as $m) {
            // determine pattern based on whether variable is wildcard or not
            $mm = str_replace(array(":", "+"), "", $m);
            $pattern = (strpos($m, "+") === strlen($m) -1) ? '(?P<' . $mm . '>(.+?[^/]))' : '(?P<' . $mm . '>([^/]+))';
            $requestPattern = str_replace($m, $pattern, $requestPattern);
        }
        $pm = preg_match("#^" . $requestPattern . "$#", $this->request->getPathInfo(), $parameters);
        if (false === $pm) {
            throw new RequestException("regex for path matching failed");
        }
        if (0 === $pm) {
            // request path does not match pattern
            return false;
        }
        foreach ($parameters as $k => $v) {
            if (!is_string($k)) {
                unset($parameters[$k]);
            }
        }
        // request path matches pattern!
        return call_user_func_array($callback, array_values($parameters));
    }
}
