<?php

use App\Helpers\DebugArray;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use App\Middleware\ApiKeyVerifier;
use App\Middleware\AccessVerifier;
use App\Providers\AclServiceProvider;
use App\Providers\DatabaseServiceProvider;

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

// Register service providers
$datbaseProvider = new DatabaseServiceProvider();
//$aclProvider = new AclServiceProvider();

$datbaseProvider->register($app);
//$aclProvider->register($app);

//phpinfo();

// Register routes from separate files
require_once __DIR__ . '/src/routes/bookingfrontend/routes.php';
require_once __DIR__ . '/src/routes/booking/routes.php';
require_once __DIR__ . '/src/routes/site.php';
//require all routes


//App\Helpers\DebugArray::debug($app);

// Set the displayErrorDetails setting. This is off by default
$app->addErrorMiddleware(true, true, true);

// Start the session with a specific session id
$session = (new Middlewares\PhpSession())->name('portico_php_session');

// Boot service providers


// Run the Slim app
$app->run();
