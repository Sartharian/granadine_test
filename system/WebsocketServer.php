<?php 

/* 
    Este modelo se ejectua directamente desde el binario de php
    /usr/bin/php archivo_server.php
    
    archivo_server.php

    require_once __DIR__ . "/WebsocketServer.php";

    $ws = new \App\WebsocketServer("0.0.0.0", 8081, [""=>""]);

    echo "Iniciando WebSocket...\n";
    $ws->run();
*/

class WebsocketServer{
    protected string $dir;
    protected int $port;
    protected bool $escuchando = false;
    protected $connection;
    protected array $listeners = [];
    protected $commands;

    public function __construct($address, $port = 5000, $commands = null){
        $this->dir = $address;
        $this->port = $port;

        $this->commands = $commands;

        $this->prepare();
    }
    protected function prepare(){
        $this->connection = socket_create(AF_INET, SOCK_STREAM, SOL_TPC);
        if(!$this->connection){
            throw new \Exception("No se pudo crear un canal de websocket". socket_strerror(socket_last_error()));
        }
        // Reutiliza el puerto
        socket_set_option($this->connection, SOL_SOCKET, SO_REUSEADDR, 1) or die("Failed: socket_option()");
        // tiempo de espera 
        socket_set_option($this->connection, SOL_SOCKET, SO_RCVBUF, 3); 

        if(!socket_bind($this->connection, $this->dir, $this->port)){
            throw new \Exception("No se pudo enlazar el weboscket en {$this->dir} puerto {$this->port}" );
        }

        socket_listen($this->connection);
    }
    // Punto de ejecución del websocket
    public function run(){
        $this->escuchando = true;

        while($this->escuchando){
            $readClients = array_merge([$this->connection], $this->listeners);
            $write = $except = null;

            if(@socket_select($readClients, $write, $except, 0) < 1){
                continue;
            }

            //conexión entrante
            if(in_array($this->connection, $readClients)){
                $this->socketAccept();
                unset($readClients[array_search($this->connection, $readClients)]);
            }
            
            //Actividad
            foreach($readClients as $cliente){
                $this->receive($cliente);
            }
        }
        // Saliendo del loop, apagamos.
        $this->poweroff();
    }
    // Detener el servicio
    public function stop(){
        $this->escuchando = false;
    }
    // Fuerza detención del servicio
    public function poweroff(){
        foreach($this->listeners as $client){
            @socket_shutdown($client);
            @socket_close($client);
        }
        @socket_shutdown($this->connection);
        @socket_close($this->connection);
    }
    // enviar u mensaje
    public function send($listener, $msg){
        $f = $this->mask($msg);
        @socket_write($listener, $f);
    }
    // Anunciar un mensaje entre los clientes
    public function broadcast($msg){
        if(!$this->escuchando) return;

        foreach($this->listeners as $p => $client){
            //tomar en cuenta esto (conexion sigue viva)
            if(false === @socket_getpeername($client, $ip)){
                unset($this->listeners[$p]); continue;
            }

            $this->send($client, $msg);
        }
    }
    // Establecimiento de comunicación y contrato
    protected function socketAccept(){
        $client = @socket_accept($this->connection);
        if(!client) return;

        $this->listeners[] = $client;
        $head = @socket_read($client, 1024);
        $this->doHandshake($head, $client);

        socket_getpeername($client, $ip);
    }
    // Logica de escucha e interacción con los clientes
    protected function receive($client){
        // Lectura de datos del canal
        while($status = @socket_recv($client, $buff, 1024, 0) > 0){
            $msg = $this->unmask($buff);

            if(!empty($msg)){
                $data = json_decode($msg, true);
                if(JSON_ERROR_NONE !== json_last_error()){
                    $this->send($client, "¿que dijiste?");
                    continue;
                }

                $response = $this->command ? $this->command->procesar($msg) : $msg;

                $this->send($client, $response);
            }
        }

        // para el caso que se interrumpa la conexión....
        $currBuff =  @socket_read($sock, 1024, PHP_NORMAL_READ);
        if(false === $currBuff){
            $this->disconnect($client);
        }
    }
    // Desvincular cliente
    protected function disconnect($client){
        $pos = array_search($client, $this->listeners);
        if(false !== $pos){
            unset($this->listeners[$pos]);
        }
        @socket_close($client);
    }
    // Contrato
    protected function doHandshake($head, $client){
        $lines = reg_split("/\r\n/", $head);
        $headers = [];

        foreach($lines as $line){
            if(preg_match("/\A(\S): (.*)\z/", trim($line), $m)){
                $headers[$m[1]] = $m[2];
            }
        }

        $secureKey = $headrs["Sec-WebSocket-Key"];
        $cyp = sha1($secureKey. "258EAFA5-E914-47DA-95CA-C5AB0DC85B11");
        $secAccpt = base64_encode(pack('H*', $cyp));

        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                   "Upgrade: WebSocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "WebSocket-Origin: $this->dir\r\n" .
                   //"WebSocket-Location: ws://$this->dir:$this->port/websocket.php\r\n" .
                   "Sec-WebSocket-Version: 13\r\n" .
                   "Sec-WebSocket-Accept:$secAccpt\r\n\r\n";

         @socket_write($client, $upgrade, strlen($upgrade));
    }
    // -- rutinas propias del protocolo
    private function unmask($info){
        $lng = ord($info[1]) & 127;

        if($lng === 126){
            $masks = substr($info, 4, 4);
            $pl = substr($info, 8);
        }elseif($lng){
            $masks = substr($info, 10, 4);
            $pl = substr($info, 14);
        }else{
            $masks = substr($info, 2, 4);
            $pl = substr($info, 6);
        }

        $txt = "";
        for($i=0;$i<strlen($pl);$i++){
            $txt .= $pl[$i] ^ $masks[$i % 4];
        }

        return $txt;
    }
    // -- rutinas propias del protocolo
    private function mask($info){
        $b1 = 0x81;
        $lng = strlen($info);

        if($lng <=125){
            $head = pack("CC", $b1, $lng);
        }elseif($Lng<=65535){
            $head = pack("CCn", $b1, 126, $lng);
        }else{
            $header = $pack("CCJ", $b1, 127, $lng);
        }

        return $head.$info;
    }


}
?>