<?php

namespace App\modules\bookingfrontend\controllers;

use App\modules\bookingfrontend\models\Building;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\modules\phpgwapi\services\Settings;
use App\Database\Db;



class BuildingController
{
    private $db;
    private $userSettings;

    public function __construct(ContainerInterface $container)
    {
        $this->db = Db::getInstance();
        $this->userSettings = Settings::getInstance()->get('user');
    }

    private function getUserRoles()
    {
        return $this->userSettings['groups'] ?? [];
    }

    public function index(Request $request, Response $response): Response
    {
        $maxMatches = isset($this->userSettings['preferences']['common']['maxmatchs']) ? (int)$this->userSettings['preferences']['common']['maxmatchs'] : 15;
        $queryParams = $request->getQueryParams();
        $start = isset($queryParams['start']) ? (int)$queryParams['start'] : 0;
        $perPage = isset($queryParams['results']) ? (int)$queryParams['results'] : $maxMatches;

        $sql = "SELECT * FROM bb_building ORDER BY id";
        if($perPage > 0)
        {
            $sql .= " LIMIT :limit OFFSET :start";
        }

        try {
            $stmt = $this->db->prepare($sql);
            if ($perPage > 0) {
                $stmt->bindParam(':limit', $perPage, \PDO::PARAM_INT);
                $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $buildings = array_map(function($data) {
                $building = new Building($data);
                return $building->serialize($this->getUserRoles());
            }, $results);

            $response->getBody()->write(json_encode($buildings));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $error = "Error fetching buildings: " . $e->getMessage();
            $response->getBody()->write(json_encode(['error' => $error]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $buildingId = $args['id'];

        try {
            $sql = "SELECT * FROM bb_building WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $buildingId, \PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$result) {
                $response->getBody()->write(json_encode(['error' => 'Building not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $building = new Building($result);
            $response->getBody()->write(json_encode($building->serialize($this->getUserRoles())));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $error = "Error fetching building: " . $e->getMessage();
            $response->getBody()->write(json_encode(['error' => $error]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}