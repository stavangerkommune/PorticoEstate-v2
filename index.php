<?php

use App\Helpers\DebugArray;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use App\Middleware\ApiKeyVerifier;
use App\Middleware\AccessVerifier;
use App\Providers\AclServiceProvider;
use App\Providers\DatabaseServiceProvider;

require_once __DIR__ . '/vendor/autoload.php';
ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');


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

// Add your settings to the container
$settings = require_once __DIR__ . '/config/database.php';
require_once SRC_ROOT_PATH . '/Helpers/Translation.php';
require_once SRC_ROOT_PATH . '/Helpers/Sanitizer.php';

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
//$session = (new Middlewares\PhpSession())->name('portico_php_session');

// Boot service providers


// Run the Slim app
$app->run();
