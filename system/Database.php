<?php 
class Database{

    public static function connect(array $def){
        if(null === $def){
            throw new \Exception("No se ha indicado una configuración para inciar la base de datos");
        }

        if(!isset($def["driver"])){
            throw new \Exception("Falta la definción del driver");
        }

        $drv = ucfirst(strtolower($def["driver"]));
        $udrv = $def["subdriver"] ?? null;

        if($udrv){
            $udrv = ucfirst(strtolower($udrv));
        }else{
            if($drv === 'Pdo'){
                $desc = $def["dsn"] ?? '';
                if(str_starts_with($desc, 'mysql:'))
                    $udrv = "Mysql";
                elseif(str_starts_with($desc, "sqlite:"))
                    $udrv = "Sqlite";
                elseif(str_starts_with($desc, "pgsql:"))
                    $udrv = "Postgresql";
                elseif(str_starts_with($desc, "sqlsvr:"))
                    $udrv = "Sqlsrv";
            }
        }

        if(!$udrv){
            throw new \Exception("Falta la definición del subdriver");
        }

        include_once __DIR__.'/dbdrivers/'.strtolower($udrv).'.php';

        if(!class_exists($udrv)){
            throw new \Exception("El driver solicitado no es compatible");
        }
        $DB = new $udrv($def);
        if(method_exists($DB, "initialize")) $DB->initialize();

        return $DB->pdo();
    }
}?>