<?php 
class Output{
    public function __construct(){

    }

    public function json($data, int $state = null){
        http_response_code($state ?? $state = null);
        header("Content-type", "application/json; charset=utf-8");
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);   
    }
}
?>