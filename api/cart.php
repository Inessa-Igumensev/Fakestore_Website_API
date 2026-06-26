<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cart.php';

// CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight-Request für CORS beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$cart = new Cart($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
   
if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(["error" => "User-ID fehlt"]);
        exit;
    }

    $user_id = (int)$_GET["user_id"];
    $result = $cart->get_user_cart($user_id);

    echo json_encode($result);
}
