<?php
// includes/Database.php

class Database {
    private $host = "vs-db.cnugewiui8gy.ap-southeast-2.rds.amazonaws.com";
    private $dbname = "ecweb";
    private $username = "admin";
    private $password = "VS12345%54321";
    private $pdo;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname};charset=utf8", 
                                 $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}
?>