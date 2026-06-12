<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../lib/jwt_config.php';

// CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight-Request beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Nur POST möglich
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        "error" => "Methode nicht erlaubt"
    ]);

    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    http_response_code(400);

    echo json_encode([
        "error" => "Ungültiges JSON"
    ]);

    exit;
}

if (
    !isset($data['username'], $data['password']) ||
    trim($data['username']) === '' ||
    trim($data['password']) === ''
) {
    http_response_code(400);

    echo json_encode([
        "error" => "Username und Passwort erforderlich"
    ]);

    exit;
}

try {
    $loggedIn = $user->login(
        trim($data['username']),
        $data['password']
    );

    if ($loggedIn === false) {
        http_response_code(401);

        echo json_encode([
            "error" => "Ungültige Anmeldedaten"
        ]);

        exit;
    }

    $currentTime = time();

    $payload = [
        "user_id" => (int) $loggedIn["user_id"],
        "username" => $loggedIn["username"],
        "role" => $loggedIn["role"],
        // Token wurde jetzt erstellt
        "iat" => $currentTime,
        // Token darf ab jetzt verwendet werden
        "nbf" => $currentTime,
        // Zwei Stunden gültig
        "exp" => $currentTime + (2 * 60 * 60)
    ];


    $token = createJwt($payload, JWT_SECRET);

    http_response_code(200);

    echo json_encode([
        "user" => $loggedIn,
        "token" => $token,
        "expires_in" => 7200
    ]);
} catch (Throwable $error) {
    error_log(
        "Login-Fehler: " . $error->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "error" => "Interner Serverfehler"
    ]);
}

exit;