<?php

namespace App\modules\bookingfrontend\controllers;

use App\modules\bookingfrontend\helpers\UserHelper;
use App\modules\bookingfrontend\models\User;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

/**
 * @OA\Tag(
 *     name="User",
 *     description="API Endpoints for User"
 * )
 */
class BookingUserController
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @OA\Get(
     *     path="/bookingfrontend/user",
     *     summary="Get user details",
     *     tags={"User"},
     *     @OA\Response(
     *         response=200,
     *         description="User details",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     )
     * )
     */
    public function index(Request $request, Response $response): Response
    {
        try
        {
            $bouser = new UserHelper();
            $userModel = new User($bouser);
            $serialized = $userModel->serialize();

            $response->getBody()->write(json_encode($serialized));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e)
        {
            $error = "Error fetching user details: " . $e->getMessage();
            $response->getBody()->write(json_encode(['error' => $error]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}