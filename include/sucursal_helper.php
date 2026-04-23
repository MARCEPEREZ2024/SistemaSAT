<?php

function getSucursales($conn, $onlyActive = true) {
    $sql = "SELECT * FROM sucursales";
    if ($onlyActive) {
        $sql .= " WHERE estado = 'activo'";
    }
    $sql .= " ORDER BY nombre";
    return $conn->query($sql);
}

function getSucursalActual($conn) {
    if (!isset($_SESSION['sucursal_id'])) {
        return getSucursalDefault($conn);
    }
    
    $stmt = $conn->prepare("SELECT * FROM sucursales WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['sucursal_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $sucursal = $result->fetch_assoc();
    $stmt->close();
    
    return $sucursal ?: getSucursalDefault($conn);
}

function getSucursalDefault($conn) {
    $stmt = $conn->prepare("SELECT * FROM sucursales WHERE estado = 'activo' ORDER BY id LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $sucursal = $result->fetch_assoc();
    $stmt->close();
    
    if ($sucursal) {
        $_SESSION['sucursal_id'] = $sucursal['id'];
        $_SESSION['sucursal_nombre'] = $sucursal['nombre'];
    }
    
    return $sucursal;
}

function cambiarSucursal($conn, $sucursal_id) {
    $stmt = $conn->prepare("SELECT * FROM sucursales WHERE id = ? AND estado = 'activo'");
    $stmt->bind_param("i", $sucursal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sucursal = $result->fetch_assoc();
    $stmt->close();
    
    if ($sucursal) {
        $_SESSION['sucursal_id'] = $sucursal['id'];
        $_SESSION['sucursal_nombre'] = $sucursal['nombre'];
        return true;
    }
    return false;
}

function puedeAccederSucursal($conn, $user_id, $sucursal_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT s.*, u.sucursal_id as user_sucursal 
        FROM sucursales s 
        LEFT JOIN usuarios u ON u.id = ?
        WHERE s.id = ?
    ");
    $stmt->bind_param("ii", $user_id, $sucursal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) return false;
    
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
        return true;
    }
    
    return $row['user_sucursal'] == $sucursal_id || is_null($row['user_sucursal']);
}

function filtroSucursal($tabla_alias = '') {
    $prefix = $tabla_alias ? $tabla_alias . '.' : '';
    if (isset($_SESSION['sucursal_id']) && $_SESSION['sucursal_id']) {
        return " AND {$prefix}sucursal_id = " . (int)$_SESSION['sucursal_id'];
    }
    return "";
}

function crearSucursal($conn, $data) {
    $stmt = $conn->prepare("
        INSERT INTO sucursales (nombre, direccion, telefono, email, responsable_id, estado)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss", 
        $data['nombre'],
        $data['direccion'],
        $data['telefono'],
        $data['email'],
        $data['responsable_id'],
        $data['estado']
    );
    $result = $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function actualizarSucursal($conn, $id, $data) {
    $stmt = $conn->prepare("
        UPDATE sucursales SET nombre = ?, direccion = ?, telefono = ?, email = ?, responsable_id = ?, estado = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssssi", 
        $data['nombre'],
        $data['direccion'],
        $data['telefono'],
        $data['email'],
        $data['responsable_id'],
        $data['estado'],
        $id
    );
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function eliminarSucursal($conn, $id) {
    if ($id == 1) return false;
    
    $stmt = $conn->prepare("UPDATE sucursales SET estado = 'inactivo' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getEstadisticasSucursal($conn, $sucursal_id) {
    $stats = [];
    
    $where = $sucursal_id ? "WHERE sucursal_id = $sucursal_id" : "";
    
    $result = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio $where");
    $stats['ordenes'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conn->query("SELECT COALESCE(SUM(costo_total), 0) as total FROM ordenes_servicio $where");
    $stats['ingresos'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM clientes $where");
    $stats['clientes'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    return $stats;
}