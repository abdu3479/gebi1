<?php
class Database {
    private $host = 'localhost';
    private $username = 'root'; // Replace with your database username
    private $password = ''; // Replace with your database password
    private $database = 'aziyan'; // Replace with your database name
    private $connection;

    public function __construct() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->connection->connect_error) {
            error_log("Database connection failed: " . $this->connection->connect_error);
            throw new Exception('Database connection failed: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
    }

    public function getConnection() {
        return $this->connection;
    }

    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>