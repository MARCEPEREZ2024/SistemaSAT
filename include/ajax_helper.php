<?php

function buscar_en_tiempo_real($query, $tabla, $campos, $limit = 10) {
    $conn = getConnection();
    
    $query = trim($query);
    if (strlen($query) < 2) {
        return [];
    }
    
    $search = "%" . $conn->real_escape_string($query) . "%";
    
    $conditions = [];
    foreach ($campos as $campo) {
        $conditions[] = "$campo LIKE ?";
    }
    
    $sql = "SELECT * FROM $tabla WHERE " . implode(' OR ', $conditions) . " LIMIT $limit";
    
    $params = array_fill(0, count($campos), $search);
    $types = str_repeat('s', count($campos));
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    return $stmt->get_result();
}

function ajax_buscar_ordenes($query) {
    $conn = getConnection();
    $search = "%" . $conn->real_escape_string($query) . "%";
    
    $sql = "SELECT o.id, o.codigo, o.estado, c.nombre as cliente, e.marca, e.modelo 
            FROM ordenes_servicio o 
            LEFT JOIN clientes c ON o.cliente_id = c.id 
            LEFT JOIN equipos e ON o.equipo_id = e.id 
            WHERE o.codigo LIKE ? OR c.nombre LIKE ? OR e.marca LIKE ? 
            ORDER BY o.fecha_ingreso DESC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    return $stmt->get_result();
}

function ajax_buscar_clientes($query) {
    $conn = getConnection();
    $search = "%" . $conn->real_escape_string($query) . "%";
    
    $sql = "SELECT id, nombre, email, telefono 
            FROM clientes 
            WHERE nombre LIKE ? OR email LIKE ? OR telefono LIKE ? OR dni LIKE ?
            ORDER BY nombre ASC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    return $stmt->get_result();
}

function ajax_buscar_inventario($query) {
    $conn = getConnection();
    $search = "%" . $conn->real_escape_string($query) . "%";
    
    $sql = "SELECT id, codigo, nombre, stock, precio_venta 
            FROM repuestos 
            WHERE estado = 'activo' AND (codigo LIKE ? OR nombre LIKE ? OR categoria LIKE ?) 
            ORDER BY nombre ASC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    return $stmt->get_result();
}

function ajax_buscar_equipos($query) {
    $conn = getConnection();
    $search = "%" . $conn->real_escape_string($query) . "%";
    
    $sql = "SELECT e.id, e.marca, e.modelo, e.serie, c.nombre as cliente 
            FROM equipos e 
            LEFT JOIN clientes c ON e.cliente_id = c.id 
            WHERE e.marca LIKE ? OR e.modelo LIKE ? OR e.serie LIKE ? 
            ORDER BY e.fecha_ingreso DESC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    return $stmt->get_result();
}