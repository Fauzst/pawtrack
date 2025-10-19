<?php
class DBConnect
{
    private $conn;

    public function connect()
    {
        $this->conn = new mysqli("localhost", "root", "", "pawtrack");
        if ($this->conn->connect_error) {
            return null; 
        }
        return $this->conn;
    }
}
?>
