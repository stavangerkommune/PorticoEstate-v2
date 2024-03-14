<?php

use App\Helpers\DebugArray;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

require __DIR__ . '/vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

// Add your settings to the container
$settings = require __DIR__ . '/config/database.php';
$containerBuilder->addDefinitions(['settings' => $settings]);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();

$providers = [
    \App\Providers\DatabaseServiceProvider::class,
];


// Register all providers
foreach ($providers as $provider)
{
     (new $provider)->register($app);
}

//phpinfo();

// Register routes from separate files
$routeProvider = new \App\Providers\RouteProvider();
$routeProvider->register($app);

//App\Helpers\DebugArray::debug($app);

// Set the displayErrorDetails setting. This is off by default
$app->addErrorMiddleware(true, true, true);

// Run the Slim app
$app->run();
