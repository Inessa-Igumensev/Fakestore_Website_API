
<?php
require_once __DIR__ . '/../lib/jwt.php';

class User
{
    private $conn;
    private $table = "users";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    //Alle Users anzeigen
    public function getUsers()
    {
        $query = 'SELECT user_id, username, email, firstname, surname, street, postal_code, country, created_at, mobile, role FROM ' . $this->table;
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Einen User durch die ID anzeigen
    public function getUserId(int $id)
    {
        $query = 'SELECT user_id, username, email, firstname, surname, street, postal_code, country, created_at, mobile, role FROM ' . $this->table . ' WHERE user_id = ?';
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }


    //Einen User erstellen
    public function createUser(string $username, string $email, string $password, string $firstname, string $surname)
    {
        if (
            empty(trim($username)) ||
            empty(trim($email)) ||
            empty(trim($password)) ||
            empty(trim($firstname)) ||
            empty(trim($surname))
        ) {
            return [
                "success" => false,
                "message" => "Bitte alle Pflichtfelder ausfüllen."
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                "success" => false,
                "message" => "Bitte eine gültige E-Mail-Adresse eingeben."
            ];
        }

        if (strlen($password) < 8) {
            return [
                "success" => false,
                "message" => "Das Passwort muss mindestens 8 Zeichen lang sein."
            ];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $query = 'INSERT INTO ' . $this->table . '(username,email,password_hash,firstname,surname) VALUES (?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sssss', $username, $email, $hash, $firstname, $surname);

        if ($stmt->execute()) {
            return [
                "success" => true,
                "message" => "User wurde erfolgreich erstellt.",
                "user_id" => $stmt->insert_id
            ];
        }

        return [
            "success" => false,
            "message" => "User konnte nicht erstellt werden."
        ];
    }

    //Login
    public function login(string $username, string $password)
    {

        if (empty(trim($username)) || empty(trim($password))) {
            return [
                "success" => false,
                "message" => "Bitte Benutzername und Passwort eingeben."
            ];
        }
        $query = 'SELECT  user_id, username, email, role,password_hash FROM ' . $this->table . ' WHERE username = ?';
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            return false;
        }

        if (!password_verify($password, $row['password_hash'])) {
            return false;
        }

        unset($row['password_hash']);

        return $row;
    }

    //Update User teilweise
    public function update_user(array $data, int $user_id)
    {
        $allowedFields = [
            "username" => "username",
            "email" => "email",
            "firstname" => "firstname",
            "surname" => "surname",
            "street" => "street",
            "postal_code" => "postal_code",
            "country" => "country",
            "mobile" => "mobile"
        ];

        $fields = [];
        $values = [];
        $types = "";

        foreach ($allowedFields as $inputKey => $valuename) {
            if (array_key_exists($inputKey, $data) && trim($data[$inputKey]) != "") {
                $fields[] = $valuename . " = ? ";
                $values[] = $data[$inputKey];
                $types .= "s";
            }
        }

        if (array_key_exists("password", $data) && trim($data["password"]) != "") {
            if (strlen($data["password"]) < 8) {
                return [
                    "success" => false,
                    "message" => "Das Passwort muss mindestens 8 Zeichen lang sein."
                ];
            }

            $fields[] = "password_hash = ?";
            $values[] = password_hash($data["password"], PASSWORD_DEFAULT);
            $types .= "s";
        }

        if (empty($fields)) {
            return [
                "success" => false,
                "message" => "Keine gültigen Felder zum Aktualisieren gesendet."
            ];
        }

        $values[] = $user_id;
        $types .= "i";

        $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$values);


        if ($stmt->execute()) {
            return [
                "success" => true,
                "message" => "User wurde aktualisiert."
            ];
        }
    }

    //Delete User
    public function deleteUser(int $userId): bool
    {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $userId);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->affected_rows === 1;
    }

    // Aktuell eingeloggten User löschen
    public function deleteMyUser(int $user_id): bool
    {
        return $this->deleteUser($user_id);
    }
}
