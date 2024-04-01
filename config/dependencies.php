<?php

use App\Controller\SetupController;
use Slim\Views\Twig;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        Twig::class => function () {
            $loader = new \Twig\Loader\FilesystemLoader(SRC_ROOT_PATH . '/Templates');
            return new \Slim\Views\Twig($loader);
        },
        SetupController::class => function (ContainerInterface $c) {
            $twig = $c->get(Twig::class);
            return new SetupController($twig);
        },
    ]);
};