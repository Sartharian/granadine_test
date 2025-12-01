<?php 

class Mysql{
    protected PDO $pdo;
    protected array $preset;

    public function __construct(array $preset){
        $this->preset = $preset;
    }

    public function initialize(){
        if(!isset($this->preset["dsn"])){
            $this->preset["dsn"] = sprintf("mysql:%shost=%s;user=%s;password=%s;dbname=%s;charset=%s",
                (isset($this->preset['socket']) ? "prefix=".$this->preset['socket'] .";" : ""), 
                $this->preset['host'],
                $this->preset['user'],
                $this->preset['password'],
                $this->preset['database'],
                $this->preset['charset'] ?? 'utf8mb4');
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