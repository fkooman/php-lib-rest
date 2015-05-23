# Introduction
Writing a plugin is very easy. 

    <?php

    use fkooman\Http\Request;
    use fkooman\Rest\ServicePluginInterface;

    class MyPlugin implements ServicePluginInterface 
    {
        public function execute(Request $request, array $matchPluginConfig)
        {
            // my logic
        }
    }

You can return any object from the `execute()` method which will be available
to the callback.

        public function execute(Request $request, array $matchPluginConfig)
        {
            $obj = new StdClass();
            $obj->foo = 'bar';
            return $obj;
        }

The plugin can be registered, like any other plugin, by using the loader of
the `Service` class:

    $myPlugin = new MyPlugin();

    $service = new Service();
    $service->registerOnMatchPlugin($myPlugin);

If you want to disable the plugin by default, i.e. allow per route enabling of
the plugin you can use this:

    $service->registerOnMatchPlugin($myPlugin, array('defaultDisable' => true));

You can then use the route configuration to manipulate this:

    $service->get(
        '/foo',
        function(Request $request, StdClass $s) {
            // my logic
        },
        array(
            'MyPlugin' => array('enabled' => true)
        )
    );

You can also disable the plugin, if you leave it enabled by default by setting
`enabled` to `false`.
