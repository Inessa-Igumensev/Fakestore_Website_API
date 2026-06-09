<?php 


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../lib/jwt.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $data = json_decode(file_get_contents("php://input"), true);

    if(!isset($data['username'],$data['password'])){
        http_response_code(400);
        echo json_encode(["error" => "Username und Passwort erforderlich"]);
        exit;
    }

    $loggedIn= $user-> login($data['username'],$data['password']);
  if ($loggedIn === false) {
      http_response_code(401);
      echo json_encode(["error" => "Ungültige Anmeldedaten"]);
      exit;
  }   

    $payload =[
        'user_id'  => $loggedIn['user_id'],
        'username' => $loggedIn['username'],
        'exp'      => time() + 3600
    ];

    $secret ="ASDFJABFBAZBFZASBF";
    $token = createJwt($payload,$secret);

      echo json_encode([
          "user"  => $loggedIn,
          "token" => $token
      ]);


}
else{
    http_response_code(405);
    echo json_encode(["error" => "Username und Passwort erforderlich"]);
}

?>