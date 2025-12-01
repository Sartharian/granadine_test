<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
require __DIR__.'/system/App.php';
require __DIR__.'/system/Router.php';
require __DIR__.'/system/Loader.php';
require __DIR__.'/system/QueryBuilder.php';
require __DIR__.'/system/Database.php';
require __DIR__.'/system/Session.php';
require __DIR__.'/system/Input.php';
require __DIR__.'/system/Security.php';

try{
    $config = array();
    /*$config['database'] = [
        'driver'    => 'Pdo',
        'subdriver' => 'mysql',
        'database'  => 'test',
        'user'      => 'root',
        'password'  => 'trinitycore',
        'host'      => '127.0.0.1',
        'port'      => 3306,
        'chartset'  => 'utf8',
        'socket' => '/opt/lampp/var/mysql/mysql.sock'
    ];*/
    $app = new App($config);
    $app->run();
}catch(\Throwable $ex){
    echo $ex;
}

?>