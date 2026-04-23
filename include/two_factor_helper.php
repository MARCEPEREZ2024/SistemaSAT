<?php

function generarSecreto2FA() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < 16; $i++) {
        $secret .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $secret;
}

function obtenerUrl2FA($secret, $email, $issuer = 'SistemaSAT') {
    $issuer = urlencode($issuer);
    $email = urlencode($email);
    $secret = str_replace(' ', '', $secret);
    
    return "otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}";
}

function verificarCodigo2FA($secret, $codigo) {
    $secret = str_replace(' ', '', $secret);
    
    $time = floor(time() / 30);
    
    for ($offset = -1; $offset <= 1; $offset++) {
        $t = $time + $offset;
        $codigoCalculado = generarCodigoTOTP($secret, $t);
        
        if (hash_equals($codigoCalculado, str_pad($codigo, 6, '0', STR_PAD_LEFT))) {
            return true;
        }
    }
    
    return false;
}

function generarCodigoTOTP($secret, $time) {
    $secret = base32Decode($secret);
    $time = pack('N', $time);
    $time = str_pad($time, 8, "\x00", STR_PAD_LEFT);
    
    $hash = hash_hmac('sha1', $time, $secret, true);
    
    $offset = ord(substr($hash, -1)) & 0x0F;
    $binary = substr($hash, $offset, 4);
    $binary = unpack('N', $binary)[1];
    
    $binary = $binary & 0x7FFFFFFF;
    
    return str_pad($binary % 1000000, 6, '0', STR_PAD_LEFT);
}

function base32Decode($base32) {
    $base32 = strtoupper($base32);
    $base32 = str_replace('=', '', $base32);
    
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    
    for ($i = 0; $i < strlen($base32); $i++) {
        $val = strpos($chars, $base32[$i]);
        if ($val === false) continue;
        $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
    }
    
    $bytes = str_split($bits, 8);
    $output = '';
    
    foreach ($bytes as $byte) {
        if (strlen($byte) === 8) {
            $output .= chr(bindec($byte));
        }
    }
    
    return $output;
}

function verificarYActivar2FA($conn, $user_id, $codigo) {
    $stmt = $conn->prepare("SELECT two_factor_secret, email FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user || !$user['two_factor_secret']) {
        return ['success' => false, 'error' => '2FA no configurado'];
    }
    
    if (verificarCodigo2FA($user['two_factor_secret'], $codigo)) {
        $stmt = $conn->prepare("UPDATE usuarios SET two_factor_enabled = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true, 'message' => '2FA activado correctamente'];
    }
    
    return ['success' => false, 'error' => 'Código inválido'];
}

function generarQR2FA($conn, $user_id) {
    $stmt = $conn->prepare("SELECT nombre, email, two_factor_secret FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        return null;
    }
    
    if (!$user['two_factor_secret']) {
        $user['two_factor_secret'] = generarSecreto2FA();
        
        $stmt = $conn->prepare("UPDATE usuarios SET two_factor_secret = ? WHERE id = ?");
        $stmt->bind_param("si", $user['two_factor_secret'], $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    $email = $user['email'] ?: $user['nombre'];
    $url = obtenerUrl2FA($user['two_factor_secret'], $email);
    
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($url);
    
    return [
        'secret' => $user['two_factor_secret'],
        'qr' => $qrUrl,
        'url' => $url
    ];
}

function desactivar2FA($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE usuarios SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true];
}

function requiere2FA($conn, $user_id) {
    $stmt = $conn->prepare("SELECT two_factor_enabled FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row && $row['two_factor_enabled'] == 1;
}

function generarCodigoBackup2FA($conn, $user_id) {
    $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("
        INSERT INTO codigos_verificacion (usuario_id, codigo, tipo, expires_at)
        VALUES (?, ?, '2fa_backup', DATE_ADD(NOW(), INTERVAL 10 MINUTE))
    ");
    $stmt->bind_param("is", $user_id, $codigo);
    $stmt->execute();
    $stmt->close();
    
    return $codigo;
}

function verificarCodigoBackup2FA($conn, $user_id, $codigo) {
    $stmt = $conn->prepare("
        SELECT id FROM codigos_verificacion
        WHERE usuario_id = ? AND codigo = ? AND tipo = '2fa_backup' AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    if ($exists) {
        $stmt = $conn->prepare("DELETE FROM codigos_verificacion WHERE usuario_id = ? AND tipo = '2fa_backup'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    return $exists;
}