<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/users.php';

//CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, DELETE,PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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
    } else {
        $users = $user->getUsers();
        echo json_encode($users);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['username'], $data['email'], $data['password'], $data['firstname'], $data['surname'])) {
        http_response_code(400);
        echo json_encode(["error" => "Fehlende Felder"]);
        exit;
    }

    $newId = $user->createUser(
        $data['username'],
        $data['email'],
        $data['password'],
        $data['firstname'],
        $data['surname']
    );
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

    if (!isset($data['user_id'], $data['username'], $data['email'], $data['password_hash'], $data['firstname'], $data['surname'])) {
        http_response_code(400);
        echo json_encode(["error" => "Fehlende Felder"]);
        exit;
    }

    $success = $user-> update_user(
        (int)$data["user_id"],
        $data["username"],
        $data["email"],
        $data["password_hash"],
        $data['firstname'],
        $data["surname"],
    );
    if ($success) {
        echo json_encode(["message" => "User wurde aktualisiert"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "User konnte nicht aktualisiert werden"]);
    }
}
