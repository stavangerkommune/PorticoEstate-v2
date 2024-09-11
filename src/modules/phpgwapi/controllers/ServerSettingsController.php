<?php

namespace App\modules\phpgwapi\controllers;

use App\modules\phpgwapi\models\ServerSettings;
use App\modules\phpgwapi\services\Settings;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ServerSettingsController
{
    private $settings;

    public function __construct(ContainerInterface $container)
    {
        $this->settings = Settings::getInstance();
    }

    /**
     * @OA\Get(
     *     path="/phpgwapi/server-settings",
     *     summary="Get server settings",
     *     tags={"Server Settings"},
     *     @OA\Response(
     *         response=200,
     *         description="Server settings",
     *         @OA\JsonContent(ref="#/components/schemas/ServerSettings")
     *     )
     * )
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $serverSettings = $this->settings->get('server');
            $model = new ServerSettings($serverSettings);
            $serialized = $model->serialize();

            $response->getBody()->write(json_encode($serialized));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $error = "Error fetching server settings: " . $e->getMessage();
            $response->getBody()->write(json_encode(['error' => $error]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}