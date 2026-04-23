<?php

function enviarWhatsApp($telefono, $mensaje, $config = []) {
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    if (strlen($telefono) < 10) {
        return ['success' => false, 'error' => 'Teléfono inválido'];
    }
    
    $provider = $config['provider'] ?? 'wppconnect';
    
    switch ($provider) {
        case 'twilio':
            return enviarWhatsAppTwilio($telefono, $mensaje, $config);
        case 'wppconnect':
            return enviarWhatsAppWPPConnect($telefono, $mensaje, $config);
        case 'chatapi':
            return enviarWhatsAppChatAPI($telefono, $mensaje, $config);
        default:
            return ['success' => false, 'error' => 'Proveedor no soportado'];
    }
}

function enviarWhatsAppWPPConnect($telefono, $mensaje, $config) {
    $url = $config['url'] ?? 'http://localhost:8080/sendMessage';
    $session = $config['session'] ?? 'default';
    
    $data = [
        'phone' => $telefono,
        'message' => $mensaje
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => $response];
}

function enviarWhatsAppTwilio($telefono, $mensaje, $config) {
    $account_sid = $config['account_sid'] ?? getWhatsAppConfig('twilio_sid');
    $auth_token = $config['auth_token'] ?? getWhatsAppConfig('twilio_token');
    $from = $config['from'] ?? getWhatsAppConfig('twilio_whatsapp_from');
    
    if (!$account_sid || !$auth_token || !$from) {
        return ['success' => false, 'error' => 'Configuración de Twilio incompleta'];
    }
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
    
    $data = [
        'From' => 'whatsapp:' . $from,
        'To' => 'whatsapp:' . $telefono,
        'Body' => $mensaje
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode("$account_sid:$auth_token")
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (isset($response['sid'])) {
        return ['success' => true, 'message_id' => $response['sid']];
    }
    
    return ['success' => false, 'error' => $response['message'] ?? 'Error desconocido'];
}

function enviarWhatsAppChatAPI($telefono, $mensaje, $config) {
    $token = $config['token'] ?? getWhatsAppConfig('chatapi_token');
    $url = $config['url'] ?? 'https://api.chat-api.com/instance' . ($config['instance'] ?? getWhatsAppConfig('chatapi_instance')) . '/message';
    
    if (!$token) {
        return ['success' => false, 'error' => 'Token de ChatAPI no configurado'];
    }
    
    $data = [
        'phone' => $telefono,
        'body' => $mensaje
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if ($response['success']) {
        return ['success' => true, 'message_id' => $response['idMessage'] ?? null];
    }
    
    return ['success' => false, 'error' => $response['message'] ?? 'Error desconocido'];
}

function formatoMensajeOrden($orden, $tipo = 'nueva') {
    $mensajes = [
        'nueva' => "🆕 *Nueva Orden de Servicio*\n\n" .
                   "📋 Código: *{$orden['codigo']}*\n" .
                   "👤 Cliente: {$orden['cliente_nombre']}\n" .
                   "💻 Equipo: {$orden['marca']} {$orden['modelo']}\n" .
                   "⚡ Prioridad: {$orden['prioridad']}\n\n" .
                   "Nos contactaremos pronto.",
        'estado' => "📊 *Actualización de Orden*\n\n" .
                    "📋 Código: *{$orden['codigo']}*\n" .
                    "✅ Estado: *{$orden['estado']}*\n\n" .
                    "Gracias por confiar en nosotros.",
        'reparado' => "✅ *Orden Reparada*\n\n" .
                      "📋 Código: *{$orden['codigo']}*\n" .
                      "💰 Costo: S/ {$orden['costo_total']}\n\n" .
                      "Pass para retirar: {$orden['codigo']}",
        'entregado' => "🎉 *Orden Entregada*\n\n" .
                       "📋 Código: *{$orden['codigo']}*\n\n" .
                       "Gracias por su preferencia!"
    ];
    
    return $mensajes[$tipo] ?? $mensajes['nueva'];
}

function formatoMensajeCliente($cliente, $tipo = 'bienvenida') {
    $mensajes = [
        'bienvenida' => "👋 *Bienvenido a Sistema SAT*\n\n" .
                        "Hola *{$cliente['nombre']}*!\n" .
                        "Gracias por registrarse.\n\n" .
                        "Estamos para servirle.",
        'recordatorio' => "⏰ *Recordatorio*\n\n" .
                          "Hola *{$cliente['nombre']}*!\n" .
                          "Tiene equipos pendientes de retiro.\n" .
                          "Visítenos pronto."
    ];
    
    return $mensajes[$tipo] ?? $mensajes['bienvenida'];
}

function getWhatsAppConfig($key) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT valor FROM configuraciones WHERE clave = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['valor'] ?? null;
}

function saveWhatsAppConfig($key, $value) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        INSERT INTO configuraciones (clave, valor) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE valor = ?
    ");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
    $stmt->close();
    
    return true;
}