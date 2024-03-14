<?php
namespace App\Controllers;

use App\Helpers\DebugArray;
use PDO;
use App\Models\User;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception; // For handling potential errors

/**
 * @OA\PathItem(
 *   path="/users",
 *   @OA\Get(
 *     summary="Get all users",
 *     description="Returns all users",
 *     @OA\Response(
 *       response=200,
 *       description="A list of users",
 *       @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
 *     ),
 *     @OA\Response(
 *       response=500,
 *       description="An error occurred"
 *     )
 *   ),
 *   @OA\Post(
 *     summary="Create a new user",
 *     description="Creates a new user and returns the user information",
 *     @OA\RequestBody(
 *       required=true,
 *       @OA\JsonContent(ref="#/components/schemas/User")
 *     ),
 *     @OA\Response(
 *       response=201,
 *       description="A user",
 *       @OA\JsonContent(ref="#/components/schemas/User")
 *     ),
 *     @OA\Response(
 *       response=400,
 *       description="Invalid user data"
 *     ),
 *     @OA\Response(
 *       response=500,
 *       description="An error occurred"
 *     )
 *   )
 * )
 * @OA\PathItem(
 *   path="/users/{id}",
 *   @OA\Get(
 *     summary="Get user information",
 *     description="Returns user information",
 *     @OA\Parameter(
 *       name="id",
 *       in="path",
 *       required=true,
 *       @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *       response=200,
 *       description="A user",
 *       @OA\JsonContent(ref="#/components/schemas/User")
 *     ),
 *     @OA\Response(
 *       response=404,
 *       description="User not found"
 *     ),
 *     @OA\Response(
 *       response=500,
 *       description="An error occurred"
 *     )
 *   ),
 *   @OA\Put(
 *     summary="Update user information",
 *     description="Updates user information and returns the updated user",
 *     @OA\Parameter(
 *       name="id",
 *       in="path",
 *       required=true,
 *       @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *       required=true,
 *       @OA\JsonContent(ref="#/components/schemas/User")
 *     ),
 *     @OA\Response(
 *       response=200,
 *       description="A user",
 *       @OA\JsonContent(ref="#/components/schemas/User")
 *     ),
 *     @OA\Response(
 *       response=400,
 *       description="Invalid user data"
 *     ),
 *     @OA\Response(
 *       response=404,
 *       description="User not found"
 *     ),
 *     @OA\Response(
 *       response=500,
 *       description="An error occurred"
 *     )
 *   ),
 *   @OA\Delete(
 *     summary="Delete a user",
 *     description="Deletes a user",
 *     @OA\Parameter(
 *       name="id",
 *       in="path",
 *       required=true,
 *       @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *       response=204,
 *       description="No content"
 *     ),
 *     @OA\Response(
 *       response=404,
 *       description="User not found"
 *     ),
 *     @OA\Response(
 *       response=500,
 *       description="An error occurred"
 *     )
 *   )
 * )
 */
class UserController
{
    private $db;

    public function __construct(ContainerInterface $container)
	{
		$this->db = $container->get('db');
	}
   
	public function index(Request $request, Response $response): Response
	{
		$queryParams = $request->getQueryParams();
		$page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
		$perPage = isset($queryParams['perPage']) ? (int)$queryParams['perPage'] : 10;
		$offset = ($page - 1) * $perPage;

		$users = [];
		try {
			$sql = "SELECT * FROM bb_user ORDER BY id LIMIT :limit OFFSET :offset";
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
			$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			foreach ($results as $result) {
				$users[] = new User($result); // Create User objects
			}
		} catch (Exception $e) {
			// Handle database error (e.g., log the error, return an error response)
			$error = "Error fetching users: " . $e->getMessage();
			$response->getBody()->write(json_encode(['error' => $error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}
		$response->getBody()->write(json_encode($users));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

    public function store(Request $request, Response $response): Response
    {
        $name = $request->getParsedBodyParam('name');
        $email = $request->getParsedBodyParam('email');

        if (empty($name) || empty($email)) {
            // Handle validation error (e.g., return a 400 Bad Request response)
            $error = "Please provide name and email";
			$response->getBody()->write(json_encode(['error' => $error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        try {
            $sql = "INSERT INTO bb_user (name, email) VALUES (:name, :email)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $userId = $this->db->lastInsertId();
            $user = new User($userId, $name, $email); // Create a new User object

			$response->getBody()->write(json_encode($user));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(201); // // Return the created user (201 Created)

        } catch (Exception $e) {
            // Handle database error
            $error = "Error creating user: " . $e->getMessage();
			$response->getBody()->write(json_encode(['error' => $error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

	public function show(Request $request, Response $response, array $args): Response
	{
		$userId = $args['id'];

		try {
			$sql = "SELECT * FROM bb_user WHERE id = :id";
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':id', $userId);
			$stmt->execute();

			$result = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$result) {
				return $response->withStatus(404); // Not Found
			}
			$user = new User($result);
			$response->getBody()->write(json_encode($user));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
		} catch (Exception $e) {
			// Handle database error
			$error = "Error fetching user: "  . $e->getMessage();
			$response->getBody()->write(json_encode(['error' => $error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}
	}

    public function update(Request $request, Response $response, array $args): Response
    {
        $userId = $args['id'];
        $name = $request->getParsedBodyParam('name');
        $email = $request->getParsedBodyParam('email');

        if (empty($name) || empty($email)) {
            // Handle validation error (400 Bad Request)
            $error = "Please provide name and email";
			$response->getBody()->write(json_encode(['error' => $error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        try {
            $sql = "UPDATE bb_user SET name = :name, email = :email WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            $user = new User($userId, $name, $email); // Create a new User object
			$response->getBody()->write(json_encode($user));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(200); // OK

        } catch (Exception $e) {
			// Handle database error
			$error = "Error updating user: "  . $e->getMessage();
			$response->getBody()->write(json_encode(['error' => $error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $userId = $args['id'];

        try {
            $sql = "DELETE FROM bb_user WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return $response->withStatus(404); // Not Found
            }

            return $response->withStatus(204); // No Content
        } catch (Exception $e) {
            // Handle database error
            $error = "Error deleting user: " . $e->getMessage();
			$response->getBody()->write(json_encode(['error' => $error]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
