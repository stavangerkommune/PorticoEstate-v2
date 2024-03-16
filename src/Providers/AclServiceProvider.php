<?php

namespace App\Providers;

use App\Security\Acl;
use DI\Container;
use Slim\App;
use Exception;



class AclServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public static function register(App $app)
    {
        $container = $app->getContainer();

		$container->set('acl', function () use ($container) {
            $db = $container->get('db');
  //          print_r($db);
            return new Acl($db);
		});

     }
}