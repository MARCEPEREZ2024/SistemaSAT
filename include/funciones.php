<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

function getUserById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getAllUsers() {
    $conn = getConnection();
    return $conn->query("SELECT * FROM usuarios WHERE estado = 'activo' ORDER BY nombre");
}

function getAllClientes($search = '') {
    $conn = getConnection();
    if ($search) {
        $search = "%$search%";
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE estado = 'activo' AND (nombre LIKE ? OR email LIKE ? OR telefono LIKE ? OR dni LIKE ?) ORDER BY nombre");
        $stmt->bind_param("ssss", $search, $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result();
    }
    return $conn->query("SELECT * FROM clientes WHERE estado = 'activo' ORDER BY nombre");
}

function getClienteById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getEquiposByCliente($cliente_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM equipos WHERE cliente_id = ? AND estado = 'activo' ORDER BY fecha_ingreso DESC");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getEquipoById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT e.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono FROM equipos e JOIN clientes c ON e.cliente_id = c.id WHERE e.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getAllEquipos($search = '') {
    $conn = getConnection();
    if ($search) {
        $search = "%$search%";
        $stmt = $conn->prepare("SELECT e.*, c.nombre as cliente_nombre FROM equipos e JOIN clientes c ON e.cliente_id = c.id WHERE e.estado = 'activo' AND (e.marca LIKE ? OR e.modelo LIKE ? OR e.serie LIKE ? OR c.nombre LIKE ?) ORDER BY e.fecha_ingreso DESC");
        $stmt->bind_param("ssss", $search, $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result();
    }
    return $conn->query("SELECT e.*, c.nombre as cliente_nombre FROM equipos e JOIN clientes c ON e.cliente_id = c.id WHERE e.estado = 'activo' ORDER BY e.fecha_ingreso DESC");
}

function getOrdenById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT o.*, e.marca, e.modelo, e.serie, e.tipo_equipo, c.nombre as cliente_nombre, c.email as cliente_email, c.telefono as cliente_telefono, u.nombre as tecnico_nombre FROM ordenes_servicio o LEFT JOIN equipos e ON o.equipo_id = e.id LEFT JOIN clientes c ON o.cliente_id = c.id LEFT JOIN usuarios u ON o.tecnico_id = u.id WHERE o.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getAllOrdenes($estado = '', $search = '') {
    $conn = getConnection();
    $sql = "SELECT o.id, o.codigo, o.estado, o.prioridad, o.costo_total, o.fecha_ingreso, e.marca, e.modelo, c.nombre as cliente_nombre, c.email as cliente_email, c.telefono as cliente_telefono, u.nombre as tecnico_nombre FROM ordenes_servicio o LEFT JOIN equipos e ON o.equipo_id = e.id LEFT JOIN clientes c ON o.cliente_id = c.id LEFT JOIN usuarios u ON o.tecnico_id = u.id WHERE 1=1";
    
    if ($estado) {
        $sql .= " AND o.estado = '$estado'";
    }
    if ($search) {
        $search_esc = $conn->real_escape_string($search);
        $sql .= " AND (o.codigo LIKE '%$search_esc%' OR c.nombre LIKE '%$search_esc%' OR e.marca LIKE '%$search_esc%' OR e.modelo LIKE '%$search_esc%')";
    }
    $sql .= " ORDER BY o.fecha_ingreso DESC";
    
    return $conn->query($sql);
}

function getHistorialEstados($orden_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT es.*, u.nombre as tecnico_nombre FROM estados_seguimiento es LEFT JOIN usuarios u ON es.tecnico_id = u.id WHERE es.orden_id = ? ORDER BY es.fecha DESC");
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getRepuestosByOrden($orden_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT ro.*, r.nombre, r.codigo FROM repuestos_orden ro JOIN repuestos r ON ro.repuesto_id = r.id WHERE ro.orden_id = ?");
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getAllRepuestos($search = '') {
    $conn = getConnection();
    if ($search) {
        $search = "%$search%";
        $stmt = $conn->prepare("SELECT * FROM repuestos WHERE estado = 'activo' AND (codigo LIKE ? OR nombre LIKE ? OR categoria LIKE ?) ORDER BY nombre");
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result();
    }
    return $conn->query("SELECT * FROM repuestos WHERE estado = 'activo' ORDER BY categoria, nombre");
}

function getRepuestoById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM repuestos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getRepuestosStockBajo() {
    $conn = getConnection();
    return $conn->query("SELECT * FROM repuestos WHERE stock <= stock_minimo AND estado = 'activo' ORDER BY stock");
}

function getMovimientosRepuesto($repuesto_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT m.*, u.nombre as usuario_nombre, o.codigo as orden_codigo FROM movimientos_inventario m LEFT JOIN usuarios u ON m.usuario_id = u.id LEFT JOIN ordenes_servicio o ON m.orden_id = o.id WHERE m.repuesto_id = ? ORDER BY m.fecha DESC");
    $stmt->bind_param("i", $repuesto_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getAllFacturas($estado = '') {
    $conn = getConnection();
    $sql = "SELECT f.*, c.nombre as cliente_nombre, o.codigo as orden_codigo FROM facturas f JOIN clientes c ON f.cliente_id = c.id JOIN ordenes_servicio o ON f.orden_id = o.id";
    if ($estado) {
        $sql .= " WHERE f.estado_pago = '$estado'";
    }
    $sql .= " ORDER BY f.fecha_emision DESC";
    return $conn->query($sql);
}

function getFacturaById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT f.*, c.nombre as cliente_nombre, c.dni, c.direccion, c.email, c.telefono, o.codigo as orden_codigo, e.marca, e.modelo FROM facturas f JOIN clientes c ON f.cliente_id = c.id JOIN ordenes_servicio o ON f.orden_id = o.id JOIN equipos e ON o.equipo_id = e.id WHERE f.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getDetalleFactura($factura_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM detalle_factura WHERE factura_id = ?");
    $stmt->bind_param("i", $factura_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getEstadisticas() {
    $conn = getConnection();
    
    $ordenesResult = $conn->query("SELECT estado, COUNT(*) as total FROM ordenes_servicio WHERE estado_orden = 'abierta' GROUP BY estado");
    $ordenes = [];
    if ($ordenesResult) {
        while ($row = $ordenesResult->fetch_assoc()) {
            $ordenes[] = $row;
        }
    }
    
    $clientes_nuevos = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE)");
    if ($result) {
        $row = $result->fetch_assoc();
        $clientes_nuevos = $row['total'] ?? 0;
    }
    
    $ingresos_mes = 0;
    $result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND estado_pago = 'pagado'");
    if ($result) {
        $row = $result->fetch_assoc();
        $ingresos_mes = $row['total'] ?? 0;
    }
    
    $ordenes_abiertas = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE estado_orden = 'abierta'");
    if ($result) {
        $row = $result->fetch_assoc();
        $ordenes_abiertas = $row['total'] ?? 0;
    }
    
    $ordenes_recientes = [];
    $result = $conn->query("SELECT o.*, c.nombre as cliente_nombre, e.marca, e.modelo FROM ordenes_servicio o LEFT JOIN clientes c ON o.cliente_id = c.id LEFT JOIN equipos e ON o.equipo_id = e.id ORDER BY o.fecha_ingreso DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ordenes_recientes[] = $row;
        }
    }
    
    return [
        'ordenes' => $ordenes,
        'clientes_nuevos' => $clientes_nuevos,
        'ingresos_mes' => $ingresos_mes,
        'ordenes_abiertas' => $ordenes_abiertas,
        'ordenes_recientes' => $ordenes_recientes
    ];
}
?>