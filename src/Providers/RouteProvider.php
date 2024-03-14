<?php

namespace App\Providers;

class RouteProvider
{
	//get routes from files in the routes directory
	public function getRoutes()
	{
		$routes = [];
		$files = glob(__DIR__ . '/../routes/*.php');
		foreach ($files as $file) {
			$routes = array_merge($routes, require $file);
		}
		return $routes;
	}


	public function register($app)
	{
		$routes = $this->getRoutes();

		foreach ($routes as $route => $data) {
			if (isset($data['group']) && $data['group'] === true) {
				$app->group($route, function ($group) use ($data) {
					foreach ($data['routes'] as $subRoute => $methods) {
						foreach ($methods as $method => $handler) {
							$group->{$method}($subRoute, $handler);
						}
					}
				});
			} else {
				foreach ($data as $method => $handler) {
					$app->{$method}($route, $handler);
				}
			}
		}
	}
}
