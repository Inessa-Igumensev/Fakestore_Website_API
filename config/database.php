<?php 

class Database{
    private $servername = "localhost";
    private $username = "benutzername";
    private $password = "123456789";
    private $dbname = "fakestore_db";


    public function getConnection(){

        //Verbindung aufbauen
        $conn = new mysqli($this->servername,$this->username,$this->password,$this->dbname);

        //Verbindung prüfen
        if ($conn->connect_error){
            die("Verbindung fehlgeschlagen: " . $conn->connect_error);
        }
        
        return $conn;
        }

}

?>