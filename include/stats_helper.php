<?php

function get_estadisticas_generales() {
    $conn = getConnection();
    
    $stats = [
        'ordenes_total' => 0,
        'ordenes_pendientes' => 0,
        'ordenes_entregadas' => 0,
        'clientes_total' => 0,
        'ingresos_mes' => 0,
        'repuestos_bajo_stock' => 0
    ];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio");
    $stats['ordenes_total'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE estado NOT IN ('entregado', 'cancelado')");
    $stats['ordenes_pendientes'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE estado = 'entregado'");
    $stats['ordenes_entregadas'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE estado = 'activo'");
    $stats['clientes_total'] = $result->fetch_assoc()['total'] ?? 0;
    
    $mes_actual = date('Y-m');
    $result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM facturas WHERE DATE(fecha_emision) LIKE '$mes_actual%'");
    $stats['ingresos_mes'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM repuestos WHERE stock <= stock_minimo AND estado = 'activo'");
    $stats['repuestos_bajo_stock'] = $result->fetch_assoc()['total'] ?? 0;
    
    return $stats;
}

function get_ordenes_por_estado() {
    $conn = getConnection();
    $data = [];
    
    $result = $conn->query("SELECT estado, COUNT(*) as total FROM ordenes_servicio GROUP BY estado");
    while ($row = $result->fetch_assoc()) {
        $data[$row['estado']] = (int)$row['total'];
    }
    
    return $data;
}

function get_ordenes_por_mes($anio = null) {
    $anio = $anio ?? date('Y');
    $conn = getConnection();
    $data = [];
    
    $result = $conn->query("
        SELECT MONTH(fecha_ingreso) as mes, COUNT(*) as total 
        FROM ordenes_servicio 
        WHERE YEAR(fecha_ingreso) = $anio 
        GROUP BY MONTH(fecha_ingreso)
    ");
    
    while ($row = $result->fetch_assoc()) {
        $data[(int)$row['mes']] = (int)$row['total'];
    }
    
    return $data;
}

function get_ingresos_por_mes($anio = null) {
    $anio = $anio ?? date('Y');
    $conn = getConnection();
    $data = [];
    
    $result = $conn->query("
        SELECT MONTH(fecha_emision) as mes, COALESCE(SUM(total), 0) as total 
        FROM facturas 
        WHERE YEAR(fecha_emision) = $anio 
        GROUP BY MONTH(fecha_emision)
    ");
    
    while ($row = $result->fetch_assoc()) {
        $data[(int)$row['mes']] = (float)$row['total'];
    }
    
    return $data;
}

function get_top_tecnicos($limit = 5) {
    $conn = getConnection();
    $data = [];
    
    $stmt = $conn->prepare("
        SELECT u.nombre, COUNT(o.id) as total_ordenes, 
               SUM(CASE WHEN o.estado = 'entregado' THEN 1 ELSE 0 END) as resueltas
        FROM usuarios u
        LEFT JOIN ordenes_servicio o ON u.id = o.tecnico_id AND o.estado = 'entregado'
        WHERE u.rol = 'tecnico' AND u.estado = 'activo'
        GROUP BY u.id
        ORDER BY resueltas DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'nombre' => $row['nombre'],
            'total' => (int)$row['total_ordenes'],
            'resueltas' => (int)$row['resueltas']
        ];
    }
    
    return $data;
}

function get_top_repuestos($limit = 10) {
    $conn = getConnection();
    $data = [];
    
    $stmt = $conn->prepare("
        SELECT r.nombre, r.codigo, SUM(ro.cantidad) as usado
        FROM repuestos r
        JOIN repuestos_orden ro ON r.id = ro.repuesto_id
        GROUP BY r.id
        ORDER BY usado DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'nombre' => $row['nombre'],
            'codigo' => $row['codigo'],
            'usado' => (int)$row['usado']
        ];
    }
    
    return $data;
}

function get_ultimas_ordenes($limit = 10) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT o.*, c.nombre as cliente_nombre, e.marca, e.modelo
        FROM ordenes_servicio o
        LEFT JOIN clientes c ON o.cliente_id = c.id
        LEFT JOIN equipos e ON o.equipo_id = e.id
        ORDER BY o.fecha_ingreso DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}