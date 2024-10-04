<?php

namespace App;

use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Dependencies
{
    public function __invoke(ContainerInterface $container)
    {
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
}
