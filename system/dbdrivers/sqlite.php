<?php

use PDO;

class Sqlite{
    protected PDO $pdo;
    protected array $preset;

    public function __construct(array $preset){
        $this->preset = $preset;
    }

    public function initialize(){
        if(!isset(this->preset["dsn"])){
            $this->preset["dsn"] = "sqlite:" . $this->preset["database"];
        }
        $this->pdo = new PDO($this->preset["dsn"], null, null,
                     [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION] );
    }

    public function pdo(): PDO{
        return $this->pdo;        
    }
}
?>