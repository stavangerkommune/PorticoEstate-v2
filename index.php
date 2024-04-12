<?php

use App\helpers\DebugArray;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use App\middleware\ApiKeyVerifier;
use App\middleware\AccessVerifier;
use App\providers\AclServiceProvider;
use App\providers\DatabaseServiceProvider;

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


require_once SRC_ROOT_PATH . '/helpers/Translation.php';
require_once SRC_ROOT_PATH . '/helpers/Sanitizer.php';
require_once SRC_ROOT_PATH . '/helpers/DebugArray.php';
require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';
// Add your settings to the container

$database_settings = require_once SRC_ROOT_PATH . '/helpers/FilterDatabaseConfig.php';

//_debug_array($database_settings);

$containerBuilder->addDefinitions(['settings' => ['db' => $database_settings]]);
//(require __DIR__ . '/config/dependencies.php')($containerBuilder);


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

//require all routes
require_once __DIR__ . '/src/routes/RegisterRoutes.php';


//App\helpers\DebugArray::debug($app);


$displayErrorDetails = true; // Set to false in production
$logErrors = true;
$logErrorDetails = true;
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);
// Get default error handler and override it with your custom error handler
$customErrorHandler = new \App\helpers\ErrorHandler($app->getResponseFactory());
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);



// Run the Slim app
$app->run();
