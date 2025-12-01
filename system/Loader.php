<?php
class Loader {
    private $layout = null;
    private $layData = [];

    public function model($name) {
        require APPPATH."models/{$name}.php";
        $class = ucfirst($name);
        $CI =& get_instance();
        $CI->$name = new $class();
    }

    public function helper($name) {
        require APPPATH."helpers/{$name}_helper.php";
    }

    private function eval_viewdest($dest, $data){
        $path = APPPATH."views/{$dest}.php";
        if(!file_exists($path))
            throw new Exception("Falta definir la ruta de la vista");

        extract($data);
        require APPPATH."views/{$dest}.php";
    }

    public function layout($name, $data=[]){
        $this->layout = $name;
        $this->layData = $data;
        return $this;
    }

    // Permite devolver un enlace a websockets
    // Nota: El servidor debe estar corriendo....
    public function websocket($dir = "127.0.0.1", $port = 8081){
        require_once __DIR__."/Websockets.php";

        $this->CI->ws = new Websockets();

        // se usa $this->CI->ws->WebsocketClient($dir, $port);
        return $this->CI->ws;
    }

    public function view($name, $data = []) {
        ob_start();
        $this->eval_viewdest($name, $data);
        $template = ob_get_clean();
        
        if(!$this->layout){
            echo $template; return;
        }
        
        $data = array_merge($this->layData, ['renderBody' => $template]);

        $this->eval_viewdest($name, $data);

        $this->layout = null;
        $this->layData = [];
    }
}
?>