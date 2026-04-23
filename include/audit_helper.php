<?php

function registrarAccion($conn, $accion, $tabla, $registro_id, $datos_anteriores = null, $datos_nuevos = null) {
    $usuario_id = $_SESSION['user_id'] ?? null;
    $usuario_nombre = $_SESSION['nombre'] ?? 'Sistema';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO audit_log (usuario_id, usuario_nombre, accion, tabla, registro_id, datos_anteriores, datos_nuevos, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "isssissss",
        $usuario_id,
        $usuario_nombre,
        $accion,
        $tabla,
        $registro_id,
        $datos_anteriores,
        $datos_nuevos,
        $ip,
        $user_agent
    );
    
    $stmt->execute();
    $stmt->close();
}

function getAuditLog($conn, $filtros = [], $pagina = 1, $por_pagina = 50) {
    $where = "1=1";
    $params = [];
    $types = "";
    
    if (!empty($filtros['usuario_id'])) {
        $where .= " AND usuario_id = ?";
        $params[] = $filtros['usuario_id'];
        $types .= "i";
    }
    
    if (!empty($filtros['accion'])) {
        $where .= " AND accion = ?";
        $params[] = $filtros['accion'];
        $types .= "s";
    }
    
    if (!empty($filtros['tabla'])) {
        $where .= " AND tabla = ?";
        $params[] = $filtros['tabla'];
        $types .= "s";
    }
    
    if (!empty($filtros['fecha_inicio'])) {
        $where .= " AND fecha >= ?";
        $params[] = $filtros['fecha_inicio'];
        $types .= "s";
    }
    
    if (!empty($filtros['fecha_fin'])) {
        $where .= " AND fecha <= ?";
        $params[] = $filtros['fecha_fin'] . ' 23:59:59';
        $types .= "s";
    }
    
    $offset = ($pagina - 1) * $por_pagina;
    
    $sql_total = "SELECT COUNT(*) as total FROM audit_log WHERE $where";
    $stmt_total = $conn->prepare($sql_total);
    if (!empty($params)) {
        $stmt_total->bind_param($types, ...$params);
    }
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $total = $result_total->fetch_assoc()['total'];
    $stmt_total->close();
    
    $sql = "SELECT * FROM audit_log WHERE $where ORDER BY fecha DESC LIMIT ? OFFSET ?";
    
    $params[] = $por_pagina;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $registros = [];
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
    }
    $stmt->close();
    
    return [
        'registros' => $registros,
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $por_pagina,
        'total_paginas' => ceil($total / $por_pagina)
    ];
}

function getEstadisticasAudit($conn, $dias = 30) {
    $stats = [];
    
    $result = $conn->query("
        SELECT accion, COUNT(*) as total 
        FROM audit_log 
        WHERE fecha >= DATE_SUB(NOW(), INTERVAL $dias DAY)
        GROUP BY accion
    ");
    while ($row = $result->fetch_assoc()) {
        $stats['acciones'][$row['accion']] = (int)$row['total'];
    }
    
    $result = $conn->query("
        SELECT tabla, COUNT(*) as total 
        FROM audit_log 
        WHERE fecha >= DATE_SUB(NOW(), INTERVAL $dias DAY)
        GROUP BY tabla
    ");
    while ($row = $result->fetch_assoc()) {
        $stats['tablas'][$row['tabla']] = (int)$row['total'];
    }
    
    $result = $conn->query("
        SELECT u.nombre, COUNT(a.id) as total
        FROM audit_log a
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.fecha >= DATE_SUB(NOW(), INTERVAL $dias DAY)
        GROUP BY a.usuario_id
        ORDER BY total DESC
        LIMIT 10
    ");
    while ($row = $result->fetch_assoc()) {
        $stats['usuarios'][$row['nombre']] = (int)$row['total'];
    }
    
    return $stats;
}

function logOrdenCreate($conn, $orden_id, $data) {
    registrarAccion($conn, 'crear', 'ordenes_servicio', $orden_id, null, json_encode($data));
}

function logOrdenUpdate($conn, $orden_id, $antes, $despues) {
    registrarAccion($conn, 'actualizar', 'ordenes_servicio', $orden_id, json_encode($antes), json_encode($despues));
}

function logOrdenDelete($conn, $orden_id, $data) {
    registrarAccion($conn, 'eliminar', 'ordenes_servicio', $orden_id, json_encode($data), null);
}

function logClienteCreate($conn, $cliente_id, $data) {
    registrarAccion($conn, 'crear', 'clientes', $cliente_id, null, json_encode($data));
}

function logClienteUpdate($conn, $cliente_id, $antes, $despues) {
    registrarAccion($conn, 'actualizar', 'clientes', $cliente_id, json_encode($antes), json_encode($despues));
}

function logLogin($conn, $usuario_id, $exitoso) {
    registrarAccion($conn, $exitoso ? 'login_exitoso' : 'login_fallido', 'usuarios', $usuario_id);
}

function logLogout($conn, $usuario_id) {
    registrarAccion($conn, 'logout', 'usuarios', $usuario_id);
}

function logBackup($conn, $accion, $detalles) {
    registrarAccion($conn, $accion, 'backup', null, null, json_encode($detalles));
}

function logConfiguracion($conn, $clave, $valor_anterior, $valor_nuevo) {
    registrarAccion($conn, 'actualizar', 'configuraciones', null, $clave . ': ' . $valor_anterior, $clave . ': ' . $valor_nuevo);
}