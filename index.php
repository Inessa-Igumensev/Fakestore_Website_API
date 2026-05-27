<?php 

include_once 'config/database.php';
include_once 'includes/users.php';

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
$db = $database ->getConnection();

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
  } 



?>