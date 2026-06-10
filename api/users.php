<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../lib/jwt.php';

//CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, DELETE,PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type,Authorization");

//Preflight-Request für Cors beantowrten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}



$database = new Database();
$db = $database->getConnection();

$user = new User($db);


if ($_SERVER['REQUEST_METHOD'] === 'GET') {




    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $result = $user->getUserId($id);

        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "User nicht gefunden"]);
        }
    } elseif (isset($_GET['all'])) {
        $users = $user->getUsers();
        echo json_encode($users);
    } else {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        // token an den Punkten splitten -> [header, payload, signature]
        $parts = explode('.', $token);
        $payload = json_decode(base64UrlDecode($parts[1]), true);
       
        if (!isset($parts[1]) || !$payload || !isset($payload['user_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Nicht eingeloggt oder ungültiger Token"]);
            exit;
        }
        $result = $user->getUserId($payload['user_id']);

        echo json_encode($result);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Ungültiges JSON"]);
        exit;
    }

    if (!isset($data['username'], $data['email'], $data['password'], $data['firstname'], $data['surname'])) {
        http_response_code(400);
        echo json_encode(["error" => "Fehlende Felder"]);
        exit;
    }

    try {
        $result = $user->createUser(
            $data['username'],
            $data['email'],
            $data['password'],
            $data['firstname'],
            $data['surname']
        );

        if ($result["success"]) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
        exit;
    } catch (Throwable $e) {
        error_log("POST /users.php Fehler: " . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            "error" => "Interner Serverfehler"
        ]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "User-ID fehlt"]);
        exit;
    }

    $success = $user->delete_user((int)$_GET['id']);

    if ($success) {
        echo json_encode(["message" => "User wurde gelöscht"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "User konnte nicht gelöscht werden"]);
    }
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['user_id'], $data['username'], $data['email'], $data['password'], $data['firstname'], $data['surname'])) {
        http_response_code(400);
        echo json_encode(["error" => "Fehlende Felder"]);
        exit;
    }

    $success = $user->update_user(
        $data["username"],
        $data["email"],
        $data["password"],
        $data["firstname"],
        $data["surname"],
        (int)$data["user_id"]
    );

    if ($success) {
        echo json_encode(["message" => "User wurde aktualisiert"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "User konnte nicht aktualisiert werden"]);
    }
}
