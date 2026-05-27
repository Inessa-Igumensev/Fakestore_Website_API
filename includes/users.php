<?php 

class User{
    private $conn;
    private $table = "users";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    //Alle Users anzeigen
    public function getUsers(){
        $query = 'SELECT user_id, username, email, firstname, surname FROM ' . $this->table;
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Einen User durch die ID anzeigen
    public function getUserId($id){
        $query = 'SELECT user_id, username, email, firstname, surname FROM ' . $this->table . ' WHERE user_id = ?';
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    //Einen User erstellen
    public function createUser($username,$email,$password,$firstname,$surname){
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $query = 'INSERT INTO ' . $this->table . '(username,email,password_hash,firstname,surname) VALUES (?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sssss',$username,$email,$hash,$firstname,$surname);
        $stmt->execute();
        return $stmt;

    }

}

?>