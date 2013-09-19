<?php

namespace fkooman\Http;

class Service
{
    /** @var array */
    private $match;

    /** @var array */
    private $supportedMethods;

    public function __construct()
    {
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

    public function run(Request $request)
    {
        foreach ($this->match as $m) {
            $response = $request->matchRest(
                $m['requestMethod'],
                $m['requestPattern'],
                $m['callback']
            );

            // false indicates not a match
            if (false !== $response) {
                if ($response instanceof Response) {
                    return $response;
                }
                $responseObj = new Response(200, "text/html");
                $responseObj->setContent($response);

                return $responseObj;
            }
        }

        // handle non matching patterns
        if (in_array($request->getRequestMethod(), $this->supportedMethods)) {
            return new Response(404);
        }

        $response = new Response(405);
        $response->setHeader("Allow", implode(",", $this->supportedMethods));

        return $response;
    }
}
