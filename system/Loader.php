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