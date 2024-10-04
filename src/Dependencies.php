<?php

namespace App;

use Psr\Container\ContainerInterface;
use Slim\App;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Dependencies
{
    public function __invoke(App $app)
    {
        $container = $app->getContainer();

        // // Ensure the container is properly set up
        // if ($container === null) {
        //     $this->logError('Container is not initialized.');
        //     return; // Avoid throwing an exception, which could lead to a 500 error
        // }

        // Database
        $container->set('db', function () {
            $capsule = new Capsule;
            $capsule->addConnection([
                'driver' => 'sqlite',
                'database' => __DIR__ . '/../database/chat.db',
                'prefix' => '',
            ]);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
            return $capsule;
        });

        // Logger
        $container->set('logger', function () {
            $logger = new Logger('chat_app');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
            return $logger;
        });
    }

    private function logError($message)
    {
        // Access the logger if it's available
        global $app; // Assuming $app is accessible here
        if ($app->getContainer()->has('logger')) {
            $logger = $app->getContainer()->get('logger');
            $logger->error($message);
        }
    }
}
