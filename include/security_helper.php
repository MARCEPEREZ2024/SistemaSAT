<?php

function sanitizer($data) {
    if (is_array($data)) {
        return array_map('sanitizer', $data);
    }
    
    if ($data === null) {
        return null;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    
    $data = str_replace(
        ["<script>", "</script>", "<iframe>", "</iframe>", "javascript:", "onerror=", "onload="],
        ["", "", "", "", "", "", ""],
        $data
    );
    
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    
    $data = preg_replace('/<[^>]*>/', '', $data);
    
    return strip_tags($data);
}

function sanitize_sql($data) {
    if (is_array($data)) {
        return array_map('sanitize_sql', $data);
    }
    
    $conn = getConnection();
    return $conn->real_escape_string($data);
}

function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Mínimo 8 caracteres";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Al menos una mayúscula";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Al menos una minúscula";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Al menos un número";
    }
    
    return $errors;
}

function generate_secure_token($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length / 2));
    }
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / 62))), 0, $length);
}

function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function get_client_ip() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
               'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key])) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function log_security_event($event_type, $details = []) {
    $conn = getConnection();
    
    $ip = get_client_ip();
    $user_id = $_SESSION['usuario_id'] ?? 0;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $detalles_json = json_encode(array_merge($details, [
        'ip' => $ip,
        'user_agent' => $user_agent
    ]));
    
    $stmt = $conn->prepare("INSERT INTO logs_seguridad (usuario_id, evento, detalles, ip, fecha) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $event_type, $detalles_json, $ip);
    $stmt->execute();
}

function check_sql_injection($data) {
    $patterns = [
        '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE)\b)/i',
        '/(\-\-|\/\*|\*\/)/',
        '/(OR|AND)\s+\d+\s*=\s*\d+/i',
        '/(;|\|\||\`)/'
    ];
    
    if (is_array($data)) {
        foreach ($data as $value) {
            if (check_sql_injection($value)) {
                return true;
            }
        }
        return false;
    }
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $data)) {
            return true;
        }
    }
    
    return false;
}

function rate_limit_check($action, $max_attempts = 10, $time_window = 300) {
    $key = "rate_limit_{$action}";
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'window_start' => $now];
    }
    
    $attempts = $_SESSION[$key];
    
    if ($now - $attempts['window_start'] > $time_window) {
        $_SESSION[$key] = ['attempts' => 0, 'window_start' => $now];
        return true;
    }
    
    if ($attempts['attempts'] >= $max_attempts) {
        return false;
    }
    
    $_SESSION[$key]['attempts']++;
    return true;
}