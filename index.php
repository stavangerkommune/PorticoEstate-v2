<?php

use App\Helpers\DebugArray;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use App\Middleware\ApiKeyVerifier;
use App\Middleware\AccessVerifier;
use App\Providers\AclServiceProvider;
use App\Providers\DatabaseServiceProvider;

require_once __DIR__ . '/vendor/autoload.php';

//phpinfo();
define('SRC_ROOT_PATH' , __DIR__ . '/src');

define('ACL_READ', 1);
define('ACL_ADD', 2);
define('ACL_EDIT', 4);
define('ACL_DELETE', 8);
define('ACL_PRIVATE', 16);
define('ACL_GROUP_MANAGERS', 32);
define('ACL_CUSTOM_1', 64);
define('ACL_CUSTOM_2', 128);
define('ACL_CUSTOM_3', 256);

//phpinfo();
$containerBuilder = new ContainerBuilder();


require_once SRC_ROOT_PATH . '/Helpers/Translation.php';
require_once SRC_ROOT_PATH . '/Helpers/Sanitizer.php';
// Add your settings to the container

$phpgw_domain = require_once __DIR__ . '/config/database.php';
$database_settings = require_once SRC_ROOT_PATH . '/Helpers/FilterDatabaseConfig.php';

//DebugArray::debug($database_settings);

$containerBuilder->addDefinitions(['settings' => ['db' => $database_settings]]);
(require __DIR__ . '/config/dependencies.php')($containerBuilder);


// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register service providers
$datbaseProvider = new DatabaseServiceProvider();
//$aclProvider = new AclServiceProvider();

$datbaseProvider->register($app);


//phpinfo();

// Register routes from separate files
require_once __DIR__ . '/src/routes/bookingfrontend/routes.php';
require_once __DIR__ . '/src/routes/booking/routes.php';
require_once __DIR__ . '/src/routes/site.php';
//require all routes


//App\Helpers\DebugArray::debug($app);


$displayErrorDetails = true; // Set to false in production
$logErrors = true;
$logErrorDetails = true;
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);
// Get default error handler and override it with your custom error handler
$customErrorHandler = new \App\Helpers\ErrorHandler($app->getResponseFactory());
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);



// Run the Slim app
$app->run();
