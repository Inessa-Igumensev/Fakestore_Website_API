<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/products.php';

//CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, DELETE,PUT,PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type,Authorization");

//Preflight-Request für Cors beantowrten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$products = new Products($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['label'])) {
        $result = $products->get_one_products($_GET['label']);
        echo json_encode($result);
    } elseif (isset($_GET['category'])) {
        $result = $products->get_product_by_category($_GET['category']);
        echo json_encode($result);
    } else {
        $result = $products->get_products();
        echo json_encode($result);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['category'], $data['label'], $data['description'], $data['stock'], $data['price'], $data['image'])) {
        http_response_code(400);
        echo json_encode(["error" => "Fehlende Felder"]);
        exit;
    }

    $success = $products->create_products(
        $data['category'],
        $data['label'],
        $data['description'],
        (int)$data['stock'],
        (float)$data['price'],
        $data['image']
    );
    if ($success) {
        http_response_code(201);
        echo json_encode(["message" => "Produkt wurde erstellt"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Produkt konnte nicht erstellt werden"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Produkt-ID fehlt"]);
        exit;
    }

    $success = $products->delete_product((int)$_GET['id']);

    if ($success) {
        echo json_encode(["message" => "Produkt wurde gelöscht"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Produkt konnte nicht gelöscht werden"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset(
            $data['product_id'],
            $data['category'],
            $data['label'],
            $data['description'],
            $data['stock'],
            $data['price'],
            $data['image']
        )
    ) {
        http_response_code(400);
        echo json_encode(["error" => "Fehlende Felder"]);
        exit;
    }

    $success = $products->update_products(
        (int)$data['product_id'],
        $data['category'],
        $data['label'],
        $data['description'],
        (int)$data['stock'],
        (float)$data['price'],
        $data['image']
    );

    if ($success) {
        echo json_encode(["message" => "Produkt wurde aktualisiert"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Produkt konnte nicht aktualisiert werden"]);
    }
}
