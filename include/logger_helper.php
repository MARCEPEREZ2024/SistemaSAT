<?php

function log_actividad($accion, $entidad = '', $entidad_id = 0, $detalles = '') {
    $conn = getConnection();
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $stmt = $conn->prepare("
        INSERT INTO actividades (usuario_id, entidad, entidad_id, accion, detalles, ip, fecha) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isisss", $usuario_id, $entidad, $entidad_id, $accion, $detalles, $ip);
    return $stmt->execute();
}

function get_actividad($entidad = '', $entidad_id = 0, $limit = 50) {
    $conn = getConnection();
    
    $sql = "SELECT a.*, u.nombre as usuario_nombre 
            FROM actividades a 
            LEFT JOIN usuarios u ON a.usuario_id = u.id 
            WHERE 1=1";
    
    if ($entidad) {
        $sql .= " AND a.entidad = '$entidad'";
    }
    if ($entidad_id > 0) {
        $sql .= " AND a.entidad_id = $entidad_id";
    }
    
    $sql .= " ORDER BY a.fecha DESC LIMIT $limit";
    
    return $conn->query($sql);
}

function get_actividad_usuario($usuario_id, $limit = 20) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT a.*, u.nombre as usuario_nombre 
        FROM actividades a 
        LEFT JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.usuario_id = ?
        ORDER BY a.fecha DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $usuario_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function get_bitacora($tabla, $registro_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT * FROM actividades 
        WHERE entidad = ? AND entidad_id = ?
        ORDER BY fecha DESC
    ");
    $stmt->bind_param("si", $tabla, $registro_id);
    $stmt->execute();
    return $stmt->get_result();
}

function format_accion($accion) {
    $textos = [
        'create' => 'creó',
        'update' => 'actualizó',
        'delete' => 'eliminó',
        'view' => 'visualizó',
        'login' => 'inició sesión',
        'logout' => 'cerró sesión',
        'export' => 'exportó',
        'import' => 'importó',
        'send' => 'envió',
        'receive' => 'recibió'
    ];
    return $textos[$accion] ?? $accion;
}

function format_entidad($entidad) {
    $textos = [
        'orden' => 'la orden',
        'cliente' => 'el cliente',
        'equipo' => 'el equipo',
        'factura' => 'la factura',
        'presupuesto' => 'el presupuesto',
        'inventario' => 'el inventario',
        'usuario' => 'el usuario'
    ];
    return $textos[$entidad] ?? $entidad;
}