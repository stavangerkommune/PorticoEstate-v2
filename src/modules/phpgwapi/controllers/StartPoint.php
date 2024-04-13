<?php

namespace App\modules\phpgwapi\controllers;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class StartPoint
{
    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $this->loadConfig();
        $this->loadLanguage();
        $this->loadSession();
        $this->loadUser();
        $this->loadHooks();
        $this->loadApp();
    }

    public function loadConfig()
    {
    }

    public function loadLanguage()
    {
    }

    public function loadSession()
    {
    }

    public function loadUser()
    {
    }

    public function loadHooks()
    {
    }

    public function loadApp()
    {
    }

    public function run(Request $request, Response $response)
    {
        $response_str = json_encode(['message' => 'Welcome to Portico API']);
        $response->getBody()->write($response_str);
        return $response->withHeader('Content-Type', 'application/json');
    }
}