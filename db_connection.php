<?php
class Database {
    private $host = "localhost";
    private $db_name = "wedora";
    private $username = "root";
    private $password = "ke1234"; // Update if your MySQL password is different
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Start session and create PDO connection
session_start();
$db = new Database();
$pdo = $db->getConnection();
?>