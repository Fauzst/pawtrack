<?php
include 'db.php';
date_default_timezone_set('Asia/Manila');

class fetchClass extends DBConnect
{


    public function __construct()
    {
        // Get the connection from DBConnect
        $this->conn = $this->connect();
        if (!$this->conn) {
            throw new Exception("Database connection failed");
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function getClientPets($clientID)
    {
        $stmt = $this->conn->prepare("SELECT * FROM pet WHERE ClientID = ?");
        $stmt->bind_param("s", $clientID);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getClientAppointments($clientID)
    {
        $stmt = $this->conn->prepare("SELECT * FROM appointment WHERE ClientID = ?");
        $stmt->bind_param("s", $clientID); // changed "i" to "s" because ClientID is varchar
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPetDetails($petID)
    {
        $stmt = $this->conn->prepare("SELECT * FROM pet WHERE PetID = ?");
        $stmt->bind_param("s", $petID);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPetRecords($petID)
    {
        $stmt = $this->conn->prepare("SELECT * FROM medhistory WHERE PetID = ?");
        $stmt->bind_param("s", $petID);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLatestPetRecord($petID)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM medhistory 
            WHERE PetID = ? 
            ORDER BY Date DESC 
            LIMIT 1
        ");
        $stmt->bind_param("s", $petID);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPetVeterinary($vetID)
    {
        $stmt = $this->conn->prepare("SELECT * FROM vet WHERE VetID = ?");
        $stmt->bind_param("s", $vetID);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
