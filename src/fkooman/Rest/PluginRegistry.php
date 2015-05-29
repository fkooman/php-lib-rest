<?php

namespace fkooman\Rest;

use fkooman\Http\Response;
use fkooman\Http\Request;

class PluginRegistry
{
    /** @var array */
    private $defaultPlugins;

    /** @var array */
    private $optionalPlugins;

    public function __construct()
    {
        $this->defaultPlugins = array();
        $this->optionalPlugins = array();
    }

    public function registerDefaultPlugin(ServicePluginInterface $plugin)
    {
        $this->defaultPlugins[] = $plugin;
    }

    public function registerOptionalPlugin(ServicePluginInterface $plugin)
    {
        $this->optionalPlugins[] = $plugin;
    }

    public function run(Request $request, Route $route)
    {
        // figure out which plugins to run
        $runPlugins = array();
        foreach ($this->defaultPlugins as $plugin) {
            $routeConfig = $route->getConfig(get_class($plugin));
            if (self::isEnabled($routeConfig, true)) {
                $runPlugins[] = $plugin;
            }
        }
        foreach ($this->optionalPlugins as $plugin) {
            $routeConfig = $route->getConfig(get_class($plugin));
            if (self::isEnabled($routeConfig, false)) {
                $runPlugins[] = $plugin;
            }
        }

        // run all plugins we need to run and keep all the objects they
        // return...
        $availableRouteCallbackParameters = array();
        foreach ($runPlugins as $plugin) {
            $routeConfig = $route->getConfig(get_class($plugin));
            $response = $plugin->execute($request, $routeConfig);
            if ($response instanceof Response) {
                // received Response from plugin, e.g. a RedirectResponse,
                // return this immediately
                return $response;
            } elseif (is_object($response)) {
                // if we get an object, just add it to the list of available
                // parameters for the callback
                $availableRouteCallbackParameters[get_class($response)] = $response;
            }
        }
        return $availableRouteCallbackParameters;
    }

    public static function isEnabled(array $routeConfig, $isDefault)
    {
        // if no 'enabled' key is present, use the default
        if (!array_key_exists('enabled', $routeConfig)) {
            return $isDefault;
        }
        // if the key is present, use it
        return $routeConfig['enabled'];
    }
}
