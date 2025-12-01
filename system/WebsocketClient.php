<?php
class WebsocketClient{
    protected $sock;

    public function __construct($dir, $port){
        $this->sock = fsockopen("tcp://$host", $port, $errno, $errstr, 2);

        if (!$this->sock) {
            throw new \Exception("No puedo conectar al WebSocket: $errstr ($errno)");
        }

        $key = base64_encode(random_bytes(16));

        $headers =
            "GET / HTTP/1.1\r\n" .
            "Host: $host:$port\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Key: $key\r\n" .
            "Sec-WebSocket-Version: 13\r\n\r\n";

        fwrite($this->socket, $headers);

         // handshake
        fread($this->socket, 1024);
    }

    public function report(){
        $msg = $this->mask(is_string($data) ? $data : json_encode($data));
        fwrite($this->socket, $msg);
    }

    protected function mask($info){
        $b1 = 0x81;
        $len = strlen($msg);

        if ($len <= 125) {
            $header = pack("CC", $b1, $len);
        } elseif ($len <= 65535) {
            $header = pack("CCn", $b1, 126, $len);
        } else {
            $header = pack("CCJ", $b1, 127, $len);
        }

        return $header . $msg;
    }
}
?>