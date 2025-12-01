<?php 
require_once __DIR__ . "/WebsocketsServer.php";
require_once __DIR__ . "/WebsocketsClient.php";

class Websockets {
    public static function server($host = "0.0.0.0", $port = 8081) {
        return new WebsocketsServer($host, $port);
    }

    public static function client($host = "127.0.0.1", $port = 8081) {
        return new WebsocketsClient($host, $port);
    }
}?>