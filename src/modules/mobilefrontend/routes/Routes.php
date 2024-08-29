<?php

use Slim\Routing\RouteCollectorProxy;
use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\mobilefrontend\helpers\HomeHelper;
use App\modules\phpgwapi\helpers\LoginHelper;
use App\modules\phpgwapi\services\Settings;




$settings = [
	'session_name' => [
		'mobilefrontend' => 'mobilefrontendsession',
		'bookingfrontend' => 'bookingfrontendsession',
		'eventplannerfrontend' => 'eventplannerfrontendsession',
		'activitycalendarfrontend' => 'activitycalendarfrontendsession'
	]
	// Add more settings as needed
];
$app->get('/mobilefrontend/', StartPoint::class . ':mobilefrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->post('/mobilefrontend/', StartPoint::class . ':mobilefrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));

$app->get('/mobilefrontend/home/', HomeHelper::class . ':processHome')->add(new SessionsMiddleware($app->getContainer(), $settings));


/*
Settings::getInstance()->set(
	'flags',
	[
		'session_name' => 'mobilefrontendsession',
		'custom_frontend' => 'mobilefrontend'
	]
);
*/
$app->get('/mobilefrontend/login_ui[/{params:.*}]', LoginHelper::class . ':processLogin');
$app->post('/mobilefrontend/login_ui[/{params:.*}]', LoginHelper::class . ':processLogin');


$app->get('/mobilefrontend/logout.php', function ()
{
	$sessions = \App\modules\phpgwapi\security\Sessions::getInstance();
	$session_id = $sessions->get_session_id();
	if ($session_id)
	{
		$sessions->destroy($session_id);
	}
	phpgw::redirect_link('/login_ui', array('cd' => 1, 'logout' => 1));
});
