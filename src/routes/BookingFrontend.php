<?php


// Path: src/routes/users.php
// return array of routes to be used in Providers/RouteProvider.php

return [
	'/BookingFrontend' => [
		'group' => true,
		'routes' => [
			'/SearchDataAll' => [
				'get' => 'App\Controllers\BookingFrontend:SearchDataAll',
			]
		]
	]
];