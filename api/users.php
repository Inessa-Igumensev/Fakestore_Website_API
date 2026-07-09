<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../lib/jwt_config.php';

// CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, DELETE, PUT, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight-Request für CORS beantworten
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

            echo json_encode([
                'error' => 'User nicht gefunden',
            ]);
        }
    } elseif (isset($_GET['all'])) {
        $users = $user->getUsers();

        echo json_encode($users);
    } else {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        $payload = verifyJwt($token, JWT_SECRET);

        if ($payload === false || !isset($payload['user_id'])) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'error' => 'Token ist ungültig oder abgelaufen.',
            ]);

            exit;
        }

        $currentUserId = filter_var(
            $payload['user_id'],
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($currentUserId === false) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'error' => 'Token enthält keine gültige User-ID.',
            ]);

            exit;
        }

        $result = $user->getUserId($currentUserId);

        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);

            echo json_encode([
                'error' => 'User nicht gefunden',
            ]);
        }
    }

    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($data)) {
        http_response_code(400);

        echo json_encode([
            'error' => 'Ungültiges JSON',
        ]);

        exit;
    }

    if (
        !isset(
            $data['username'],
            $data['email'],
            $data['password'],
            $data['firstname'],
            $data['surname']
        )
    ) {
        http_response_code(400);

        echo json_encode([
            'error' => 'Fehlende Felder',
        ]);

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

        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    } catch (Throwable $e) {
        http_response_code(500);

        echo json_encode([
            'error' => 'Interner Serverfehler',
            'details' => $e->getMessage()
        ]);
    }
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        $payload = verifyJwt($token, JWT_SECRET);

        if ($payload === false || !isset($payload['user_id'])) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'error' => 'Token ist ungültig oder abgelaufen.',
            ]);

            exit;
        }

        $currentUserId = filter_var(
            $payload['user_id'],
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($currentUserId === false) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'error' => 'Token enthält keine gültige User-ID.',
            ]);

            exit;
        }

        if (isset($_GET['id'])) {
            if (($payload['role'] ?? '') !== 'admin') {
                http_response_code(403);

                echo json_encode([
                    'success' => false,
                    'error' => 'Nur Administratoren dürfen andere Benutzer löschen.',
                ]);

                exit;
            }

            $targetUserId = filter_var(
                $_GET['id'],
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                    ],
                ]
            );

            if ($targetUserId === false) {
                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'error' => 'Ungültige Benutzer-ID.',
                ]);

                exit;
            }

            $deleted = $user->deleteUser($targetUserId);
        } else {
            $targetUserId = $currentUserId;

            $deleted = $user->deleteMyUser(
                $currentUserId
            );
        }

        if (!$deleted) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'error' => 'Benutzer wurde nicht gefunden oder konnte nicht gelöscht werden.',
            ]);

            exit;
        }

        http_response_code(200);

        echo json_encode([
            'success' => true,
            'message' => 'Benutzer wurde erfolgreich gelöscht.',
            'deleted_user_id' => $targetUserId,
        ]);
    } catch (Throwable $error) {
        error_log(
            'DELETE /users.php Fehler: ' . $error->getMessage()
        );

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'error' => 'Benutzer konnte nicht gelöscht werden.',
        ]);
    }

    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);

    $payload = verifyJwt($token, JWT_SECRET);

    if ($payload === false || !isset($payload['user_id'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'error' => 'Token ist ungültig oder abgelaufen.',
        ]);

        exit;
    }


    if (isset($_GET['id'])) {
        $targetUserId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    } else {
        $targetUserId = filter_var($payload['user_id'], FILTER_VALIDATE_INT);
    }

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ungültiges JSON.']);
        exit;
    }
    $result = $user->update_user($data, $targetUserId);

    exit;
    
} else {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'error' => 'HTTP-Methode nicht erlaubt.',
    ]);

    exit;
}
