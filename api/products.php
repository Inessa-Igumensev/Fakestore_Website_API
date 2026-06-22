<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/products.php';

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

    if (
        !isset($_POST['category'], $_POST['label'], $_POST['description'], $_POST['stock'], $_POST['price']) ||
        !isset($_FILES['image'])
    ) {
        http_response_code(400);
        echo json_encode(["error" => "Fehlende Felder"]);
        exit;
    }

    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["error" => "Fehler beim Hochladen des Bildes"]);
        exit;
    }

    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = $_FILES['image']['name'];
    $tmpName = $_FILES['image']['tmp_name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(["error" => "Nur JPG, JPEG, PNG und WEBP sind erlaubt"]);
        exit;
    }

    $newFileName = uniqid('product_', true) . '.' . $extension;
    $targetPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        http_response_code(500);
        echo json_encode(["error" => "Bild konnte nicht gespeichert werden"]);
        exit;
    }

    $imagePath = "uploads/" . $newFileName;

    $result = $products->create_products(
        $_POST['category'],
        $_POST['label'],
        $_POST['description'],
        (int) $_POST['stock'],
        (float) $_POST['price'],
        $imagePath
    );

    if ($result["success"]) {
        http_response_code(201);
        echo json_encode([
            "message" => "Produkt wurde erstellt",
            "product_id" => $result["product_id"],
            "image" => $imagePath
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "error" => "Produkt konnte nicht erstellt werden",
            "details" => $result["error"]
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Produkt-ID fehlt"]);
        exit;
    }

    $success = $products->delete_product((int) $_GET['id']);

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
        (int) $data['product_id'],
        $data['category'],
        $data['label'],
        $data['description'],
        (int) $data['stock'],
        (float) $data['price'],
        $data['image']
    );

    if ($success) {
        echo json_encode(["message" => "Produkt wurde aktualisiert"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Produkt konnte nicht aktualisiert werden"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Methode nicht erlaubt"]);
}
