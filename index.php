<?php

use App\Helpers\DebugArray;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use App\Middleware\ApiKeyVerifier;

require_once __DIR__ . '/vendor/autoload.php';


$containerBuilder = new ContainerBuilder();

// Add your settings to the container
$settings = require_once __DIR__ . '/config/database.php';
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


//$app->add(new ApiKeyVerifier($container));

//App\Helpers\DebugArray::debug($app);

// Set the displayErrorDetails setting. This is off by default
$app->addErrorMiddleware(true, true, true);

// Start the session with a specific session id
$session = (new Middlewares\PhpSession())->name('portico_php_session');

// Run the Slim app
$app->run();
