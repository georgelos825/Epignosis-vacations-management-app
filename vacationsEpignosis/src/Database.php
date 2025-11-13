<?php
namespace App;
use PDO;
class Database {
    private PDO $pdo;

    public function __construct(string $host, int $port, string $db, string $user, string $pass) {
        $dsn = "pgsql:host={$host};port={$port};dbname={$db};";
        $opts=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false];
        $this->pdo = new PDO($dsn,$user,$pass,$opts);
    }
    
    public function pdo(): PDO { return $this->pdo; }
}
