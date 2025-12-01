<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class Router {
    protected $routes = [ 'GET'=>[], 'POST'=>[], 'PUT'=>[], 'DELETE'=>[] ];

    public function add($method, $pattern, $callback) {
        $pattern = trim($pattern, '/');
        if($pattern === '') { $pattern = '/'; }
        $this->routes[$method][$pattern] = $callback;

    }
    public function get($pattern, $callback)    { $this->add('GET',    $pattern,$callback); }
    public function post($pattern, $callback)   { $this->add('POST',   $pattern,$callback); }
    public function put($pattern, $callback)    { $this->add('PUT',    $pattern,$callback); }
    public function delete($pattern, $callback) { $this->add('DELETE', $pattern,$callback); }

    protected function execute($callback, $params = []){
        if (is_callable($callback)) 
            return call_user_func($callback);
        
        if (is_array($callback)) {
            [$controller,$method] = $callback;
            require APPPATH.'controllers/'.$controller.'.php';
            $c = new $controller();
            return call_user_func_array([$c, $method], $params);
        }
    }

    protected function compile($patt){
        $patt = preg_replace("#/#", "\/", $patt);
        $patt = preg_replace("/:([A-zA-Z_][a-zA-Z0-9_]*)/", "(?P<$1>[^\/]+)", $patt);
        
        return "#^".$patt."$#";
    }

    public function dispatch() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            // Ajustar base path
            $scriptdir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            if ($scriptdir !== '' && strpos($uri, $scriptdir) === 0) {
                $uri = substr($uri, strlen($scriptdir));
            }

            $uri = trim($uri, '/');
            if ($uri === '') $uri = '/';

            // 1) Coincidencia exacta
            if (isset($this->routes[$method][$uri])) {
                return $this->execute($this->routes[$method][$uri]);
            }

            // 2) Coincidencia con parámetros
            foreach ($this->routes[$method] as $pattern => $callback) {

                // convertir pattern con parámetros → regex con grupos capturados
                $regex = preg_replace('#:([\w]+)#', '(?P<$1>[^/]+)', $pattern);
                $regex = '#^' . $regex . '$#';

                if (preg_match($regex, $uri, $matches)) {

                    // dejar solo params con nombre
                    $params = array_filter(
                        $matches,
                        fn($key) => !is_int($key),
                        ARRAY_FILTER_USE_KEY
                    );

                    return $this->execute($callback, $params);
                }
            }

            http_response_code(404);
            echo "404 — usa try/catch para encontrar al bribón";

        } catch (\Throwable $ex) {
            echo "<pre>";
            echo $ex->getMessage() . "\n";
            echo $ex->getFile() . ":" . $ex->getLine() . "\n\n";
            echo $ex->getTraceAsString();
            echo "</pre>";
        }
    }
}
?>