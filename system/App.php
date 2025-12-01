<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class App {
    public static $instance;

    protected array $props = [];

    public $router;
    public $load;
    public $session;

    public function __construct() {
        self::$instance = $this;

        $this->router = new Router();
        $this->load   = new Loader();
        $this->session = new Session();

        define('APPPATH', dirname(__DIR__, 1).'/app/');
        require APPPATH.'routes.php';
    }

    public function run() {
        $this->router->dispatch();
    }

    //Override Para el acceso normal de propiedades
    public function __get($i){
        return $this->props[$i];
    }
    public function __set($i, $v){
        $this->props[$i] = $v;
    }
}

function &get_instance() {
    return App::$instance;
}
?>