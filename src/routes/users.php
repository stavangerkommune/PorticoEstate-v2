<?php


// Path: src/routes/users.php
// return array of routes to be used in Providers/RouteProvider.php

return [
	'/users' => [
		'group' => true,
		'routes' => [
			'/' => [
				'get' => 'App\Controllers\UserController:index',
				'post' => 'App\Controllers\UserController:store',
			],
			'/{id}' => [
				'get' => 'App\Controllers\UserController:show',
				'put' => 'App\Controllers\UserController:update',
				'delete' => 'App\Controllers\UserController:destroy',
			]
		]
	]
];