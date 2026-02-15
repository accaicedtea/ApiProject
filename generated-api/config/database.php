<?php
// Configurazione database - auto-detect environment
$isAltervista = strpos($_SERVER['HTTP_HOST'] ?? '', 'altervista.org') !== false 
    || strpos($_SERVER['SERVER_NAME'] ?? '', 'altervista.org') !== false
    || isset($_SERVER['AlterVista']);

if ($isAltervista) {
    // Production - Altervista
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'my_thisisnotmysite');
    define('DB_USER', trim(' thisisnotmysite')); // Trim per rimuovere spazi
    define('DB_PASS', '');
} else {
    // Development - Local
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'acca_totem');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            throw new \RuntimeException('Database connection failed: ' . $exception->getMessage(), 500, $exception);
        }
        return $this->conn;
    }
}
?>