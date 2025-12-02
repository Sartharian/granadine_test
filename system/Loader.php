<?php
class Loader {
    public function model($name) {
        require APPPATH."models/{$name}.php";
        $class = ucfirst($name);
        $CI =& get_instance();
        $CI->$name = new $class();
    }

    public function helper($name) {
        require APPPATH."helpers/{$name}_helper.php";
    }

    protected function eval_viewdest($dest){
        $path = APPPATH."views/{$dest}.php";
        if(!file_exists($path))
            throw new Exception("No se encontró el destino de vista: " . $path);

        return $path;
    }

    // Permite devolver un enlace a websockets
    // Nota: El servidor debe estar corriendo....
    public function websocket($dir = "127.0.0.1", $port = 8081){
        require_once __DIR__."/Websockets.php";

        $this->CI->ws = new Websockets();

        // se usa $this->CI->ws->WebsocketClient($dir, $port);
        return $this->CI->ws;
    }

    public function view($name, $layout = NULL, $data = []) {
        if(is_string($layout) && $layout !== NULL){
            extract($data);
            include $this->eval_viewdest($layout);
            ob_start();
            include $this->eval_viewdest($name);
            extract($data);
            return ob_get_clean();
        }
        extract($data);
        include $this->eval_viewdest($name);
        ob_start();
        return ob_get_clean();
    }
}
?>