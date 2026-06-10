
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

    //Jetztigen User anzeigen durch den Token
    public function getUser(){
        $query = ''. $this->table . '';
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

    //Update User
    public function update_user(string $username, string $email, string $password, string $firstname, string $surname, int $user_id)
    {
         $hash = password_hash($password, PASSWORD_DEFAULT);
        $query = 'UPDATE ' . $this->table . " SET username = ? ,email = ? ,password_hash = ?, firstname = ?, surname = ? WHERE user_id =?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssi", $username, $email, $hash, $firstname, $surname, $user_id);
        return $stmt->execute();
    }

    //Delete User
    public function delete_user(int $user_id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE user_id=?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
}
