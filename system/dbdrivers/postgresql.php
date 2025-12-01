<?php 

use PDO;

class Postgresql{
    protected PDO $pdo;
    protected array $preset;

    public function __construct(array $preset){
        $this->preset = $preset;
    }

    public function initalize(){
        if(!isset($this->$preset["dsn"])){
            $this->preset["dsn"] = sprintf("pgsql:host=%s;port=%s;dbname=%s",
                $this->preset['host'],
                $this->preset['port'],
                $this->preset['database']);
        }

        $this->pdo = new PDO(
            $this->preset["dsn"],
            $this->preset["username"] ?? null,
            $this->preset["password"] ?? null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,]
        );
    }

    public function pdo(): PDO{
        return $this->pdo;
    }
}
?>