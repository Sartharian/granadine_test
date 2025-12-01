<?php
class Input {
    protected $req = ['GET'=>[], 'POST'=>[], 'PUT'=>[], 'DELETE'=>[]];
    protected $secure = NULL;
    
    function __construct($sec){
        if(!isset($sec)) exit("Se debe definir el constructor de clase necesario");

        $this->secure = $sec ?? new Security();

        $currMet = $_SERVER['REQUEST_METHOD'];

        $this->req['GET'] = $this->cleanIndex($_GET);
        $this->req['POST'] = $this->cleanIndex($_POST);

        if(in_array($currMet, ['PUT', 'DELETE'])){
            $tmp = file_get_contents("php://input");
            $brut = [];
            parse_str($tmp, $brut);

            $this->req[$currMet] = $this->cleanIndex($brut);
        }
    }
    public function get($k = NULL, $def = NULL){
        return $this->extrct('GET', $k, $def);
    }
    public function post($k = NULL, $def = NULL){
        return $this->extrct('POST', $k, $def);
    }
    public function put($k = NULL, $def = NULL){
        return $this->extrct('PUT', $k, $def);
    }
    public function delete($k = NULL, $def = NULL){
        return $this->extrct('DELETE', $k, $def);
    }
    public function isAjax(){
        return $this->det_ajax();
    }
    public function isJson($d){
        return $this->det_json($d);
    }

    // TODO: Determinar XSS
    private function det_ajax(){
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($this->secure->xss_clean($_SERVER['HTTP_X_REQUESTED_WITH'])) === 'xmlhttprequest');
    }
    private function det_json($d){
        json_decode($d);
        return json_last_error() === JSON_ERROR_NONE;
    }

    // -- Mini XSS (se debe implementar en otro archivo)
    private function clean($var){
        if(is_string($val)){
            // usar clase XSS
            return $this->secure->xss_clean($var);
        }
        return $var;
    }
    private function cleanIndex($arr){
        if(!is_array($arr)) return NULL;
        $clr = [];
        foreach($arr as $k => $v){
            $clr[$k] = $this->clean($v);
        }
        return $clr;
    }
    // -- FIN Mini XSS
    
    private function extrct($m, $k, $def){
        // Admite sólo los métodos definidos
        if(!in_array($m, $req, TRUE)){
            return NULL;
        }
        if(NULL === $k) return $this->req[$m];

        return $this->req[$m][$k] ?? $def;
    }


}
?>