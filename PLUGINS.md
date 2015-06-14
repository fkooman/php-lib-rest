# Introduction
Writing a plugin is very easy. 

    <?php

    namespace Vendor;

    use fkooman\Http\Request;
    use fkooman\Rest\ServicePluginInterface;

    class MyPlugin implements ServicePluginInterface 
    {
        public function execute(Request $request, array $routeConfig)
        {
            // my logic
        }
    }

You can return any object from the `execute()` method which will be available
to the callback.

        public function execute(Request $request, array $routeConfig)
        {
            $obj = new StdClass();
            $obj->foo = 'bar';
            return $obj;
        }

The plugin can be registered, like any other plugin, by using the loader of
the `Service` class:

    $myPlugin = new MyPlugin();

    $service = new Service();
    $service->registerDefaultPlugin($myPlugin);

This registers a *default* plugin, which means that it will be run for every
matching route. You can also register *optional* plugins that need to be 
enabled per route:

    $service->registerOptionalPlugin($myPlugin);

Assuming you registered the plugin using `registerOptionalPlugin` you can 
enable it for a specific route like this:

    $service->get(
        '/foo',
        function(Request $request, StdClass $s) {
            $response = new Response();
            $response->setBody($s->foo);
            return $response;
        },
        array(
            'Vendor\MyPlugin' => array('enabled' => true)
        )
    );

For default plugins you can disable them by using `'enabled' => false`.
