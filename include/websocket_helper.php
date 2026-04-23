<?php
function enviarNotificacionWebSocket($userId, $tipo, $mensaje, $link = null) {
    $data = [
        'type' => 'notification',
        'data' => [
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'link' => $link,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    $host = '127.0.0.1';
    $port = 8080;
    $timeout = 1;
    
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if ($socket) {
        $json = json_encode($data);
        fwrite($socket, $json);
        fclose($socket);
        return true;
    }
    
    return false;
}

function broadcastingWebSocket($tipo, $mensaje, $link = null) {
    $data = [
        'type' => 'broadcast',
        'data' => [
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'link' => $link,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    $host = '127.0.0.1';
    $port = 8080;
    $timeout = 1;
    
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if ($socket) {
        $json = json_encode($data);
        fwrite($socket, $json);
        fclose($socket);
        return true;
    }
    
    return false;
}