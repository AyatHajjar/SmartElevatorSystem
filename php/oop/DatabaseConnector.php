<?php
class DatabaseConnector {
    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    private ?PDO $pdo = null;

    public function __construct(string $host = '127.0.0.1', string $dbname = 'elevator', string $username = 'ese', string $password = 'ese') {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect(): PDO {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $this->pdo = new PDO($dsn, $this->username, $this->password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception("Database Connection Error: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}
