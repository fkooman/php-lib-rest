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
            "method" => $requestMethod,
            "pattern" => $requestPattern,
            "callback" => $callback
        );
        if (!in_array($requestMethod, $this->supportedMethods)) {
            $this->supportedMethods[] = $requestMethod;
        }
    }

    public function run(Request $request)
    {
        foreach ($this->match as $m) {
            $response = $request->matchRest($m['method'], $m['pattern'], $m['callback']);
            if (false !== $response && $response) {
                return $response;
            }
        }

        // handle non matching patterns
        if (in_array($request->getRequestMethod(), $this->supportedMethods)) {
            return new Response(404);
        }

        // handle non matching methods
        $response = new Response(405);
        $response->setHeader("Allow", implode(",", $this->supportedMethods));

        return $response;
    }
}
