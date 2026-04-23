<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function getMailerConfig() {
    require_once '../vendor/autoload.php';
    
    $smtp_host = getConfig('smtp_host') ?: 'smtp.gmail.com';
    $smtp_port = getConfig('smtp_port') ?: '587';
    $smtp_user = getConfig('smtp_user');
    $smtp_pass = getConfig('smtp_pass');
    $from_email = getConfig('smtp_from_email') ?: $smtp_user;
    $from_name = getConfig('smtp_from_name') ?: 'Servicio Técnico SAT';
    $smtp_secure = getConfig('smtp_secure') ?: 'tls';
    
    $mail = new PHPMailer(true);
    
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_user;
    $mail->Password = $smtp_pass;
    $mail->SMTPSecure = $smtp_secure;
    $mail->Port = $smtp_port;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom($from_email, $from_name);
    
    return $mail;
}

function enviarEmail($to, $subject, $body, $isHTML = true) {
    $smtp_host = getConfig('smtp_host');
    $smtp_user = getConfig('smtp_user');
    
    if (empty($smtp_host) || empty($smtp_user)) {
        return 'SMTP no configurado';
    }
    
    try {
        $mail = getMailerConfig();
        $mail->addAddress($to);
        $mail->Subject = $subject;
        
        if ($isHTML) {
            $mail->isHTML(true);
            $mail->Body = $body;
        } else {
            $mail->isHTML(false);
            $mail->Body = strip_tags($body);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

function enviarEmailConAdjunto($to, $subject, $body, $adjuntoPath, $adjuntoNombre = '') {
    $smtp_host = getConfig('smtp_host');
    $smtp_user = getConfig('smtp_user');
    
    if (empty($smtp_host) || empty($smtp_user)) {
        return 'SMTP no configurado';
    }
    
    try {
        $mail = getMailerConfig();
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;
        
        if (file_exists($adjuntoPath)) {
            $mail->addAttachment($adjuntoPath, $adjuntoNombre);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

function emailOrdenNueva($orden_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT o.*, c.nombre as cliente_nombre, c.email as cliente_email,
               e.marca, e.modelo
        FROM ordenes_servicio o
        LEFT JOIN clientes c ON o.cliente_id = c.id
        LEFT JOIN equipos e ON o.equipo_id = e.id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orden = $result->fetch_assoc();
    $stmt->close();
    
    if (!$orden || empty($orden['cliente_email'])) {
        return 'Cliente sin email';
    }
    
    $subject = 'Nueva Orden de Servicio - ' . $orden['codigo'];
    $body = "
        <h2>Nueva Orden de Servicio</h2>
        <p>Estimado cliente <strong>{$orden['cliente_nombre']}</strong>,</p>
        <p>Hemos recibido su equipo para servicio técnico.</p>
        <table style='width:100%; border-collapse: collapse;'>
            <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Código:</strong></td><td style='padding:8px; border:1px solid #ddd;'>{$orden['codigo']}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Equipo:</strong></td><td style='padding:8px; border:1px solid #ddd;'>{$orden['marca']} {$orden['modelo']}</td></tr>
            <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Estado:</strong></td><td style='padding:8px; border:1px solid #ddd;'>Recibido</td></tr>
        </table>
        <p>Nos contactaremos pronto conUpdates.</p>
        <p>Att.<br>Servicio Técnico SAT</p>
    ";
    
    return enviarEmail($orden['cliente_email'], $subject, $body);
}

function emailOrdenActualizada($orden_id, $estado) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT o.*, c.nombre as cliente_nombre, c.email as cliente_email
        FROM ordenes_servicio o
        LEFT JOIN clientes c ON o.cliente_id = c.id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orden = $result->fetch_assoc();
    $stmt->close();
    
    if (!$orden || empty($orden['cliente_email'])) {
        return 'Cliente sin email';
    }
    
    $estados = [
        'diagnostico' => 'En Diagnóstico',
        'reparacion' => 'En Reparación',
        'reparado' => 'Reparado',
        'entregado' => 'Entregado'
    ];
    
    $estadoTexto = $estados[$estado] ?? ucfirst($estado);
    
    $subject = 'Actualización de Orden - ' . $orden['codigo'];
    $body = "
        <h2>Actualización de Orden de Servicio</h2>
        <p>Estimado cliente <strong>{$orden['cliente_nombre']}</strong>,</p>
        <p>Su orden <strong>{$orden['codigo']}</strong> ha cambiado al estado: <strong>{$estadoTexto}</strong></p>
        <p>Att.<br>Servicio Técnico SAT</p>
    ";
    
    return enviarEmail($orden['cliente_email'], $subject, $body);
}