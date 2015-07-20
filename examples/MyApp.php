<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\JsonResponse;
use fkooman\Http\Exception\BadRequestException;

class MyApp extends Service
{
    public function __construct()
    {
        parent::__construct();
        $this->registerRoutes();
    }

    public function registerRoutes()
    {
        $this->get(
            '/',
            function () {
                return '
                    <html>
                    <head><title>Demo</title></head>
                    <body>
                        <form method="post" action="foo">
                            <input type="text" name="v">
                            <input type="submit">
                        </form>
                    </body>
                    </html>
                ';
            }
        );

        $this->post(
            '/foo',
            function (Request $request) {
                $v = $request->getPostParameter('v');
                if (null === $v) {
                    throw new BadRequestException('parameter "v" missing');
                }
                $response = new JsonResponse(201);
                $response->setBody(
                    array(
                        'status' => 'ok',
                    )
                );

                return $response;
            }
        );
    }
}

// run the app and send the response
$m = new MyApp();
$m->run()->send();
