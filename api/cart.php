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

    $total_price = 0.0;
    if (!isset($result["error"])) {
        foreach ($result as $item) {
            $total_price += $item['total_price'];
        }
    }

    echo json_encode([
        "items" => $result,
        "total_cart_price" => $total_price
    ]);

    exit;

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $result = $cart->add_product_to_cart((int)$data['user_id'], (int)$data['product_id'], (int)$data['quantity']);

    if ($result) {
        echo json_encode(["message" => "Produkt erfolgreich hinzugefügt"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Fehler beim Hinzufügen des Produkts"]);
    }

    exit;
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $result = $cart->update_cart((int)$data['user_id'], (int)$data['product_id'], (int)$data['quantity']);
    if ($result) {
        echo json_encode(["message" => "Warenkorb wurde erfolgreich aktualisiert"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Fehler beim Aktualisieren des Warenkorbes"]);
    }

    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(
        file_get_contents('php://input'),
        true
    );
    $result = $cart->remove_product_from_cart((int)$data['user_id'], (int)$data['product_id']);
    if ($result) {
        echo json_encode(["message" => "Produkt erfolgreich entfernt"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Fehler beim Entfernen des Produkts"]);
    }

    exit;
}
