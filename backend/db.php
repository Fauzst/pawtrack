<?php
class DBConnect
{
    protected $conn;

    public function connect()
    {
        $this->conn = new mysqli("localhost", "root", "", "pawtrack");
        if ($this->conn->connect_error) {
            return null; 
        }
        return $this->conn;
    }

    public function getConnection()
    {
        if (!$this->conn) {
            $this->connect();
        }
        return $this->conn;
    }
}
?>
