<?php
@session_start();

define('BASE_URL', 'http://localhost/SistemaSAT/');
define('IGV_PORCENTAJE', 18);

define('ESTADOS_ORDEN', [
    'recibido' => 'Recibido',
    'en_diagnostico' => 'En Diagnóstico',
    'en_reparacion' => 'En Reparación',
    'esperando_repuestos' => 'Esperando Repuestos',
    'reparado' => 'Reparado',
    'entregado' => 'Entregado',
    'cancelado' => 'Cancelado'
]);

define('COLORES_ESTADO', [
    'recibido' => '#6c757d',
    'en_diagnostico' => '#ffc107',
    'en_reparacion' => '#fd7e14',
    'esperando_repuestos' => '#0dcaf0',
    'reparado' => '#198754',
    'entregado' => '#198754',
    'cancelado' => '#dc3545'
]);

define('PRIORIDADES', [
    'baja' => 'Baja',
    'normal' => 'Normal',
    'alta' => 'Alta',
    'urgente' => 'Urgente'
]);

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function isTecnico() {
    return isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'tecnico']);
}

function hasPermission($permiso) {
    $rol = $_SESSION['rol'] ?? 'guest';
    $permisos = [
        'admin' => ['dashboard', 'ordenes', 'clientes', 'equipos', 'inventario', 'facturacion', 'presupuestos', 'cotizaciones', 'garantias', 'agenda', 'reportes', 'usuarios', 'configuracion', 'notificaciones', 'exportar'],
        'tecnico' => ['dashboard', 'ordenes', 'equipos', 'inventario', 'garantias', 'agenda'],
        'ventas' => ['dashboard', 'clientes', 'facturacion', 'presupuestos', 'cotizaciones']
    ];
    return in_array($permiso, $permisos[$rol] ?? []);
}

function requirePermission($permiso) {
    if (!hasPermission($permiso)) {
        redirect('../dashboard/index.php');
    }
}

function getMenuItems() {
    $items = [
        ['url' => 'dashboard/index.php', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'permiso' => 'dashboard'],
        ['url' => 'ordenes/listar.php', 'icon' => 'bi-ticket-detailed', 'label' => 'Órdenes', 'permiso' => 'ordenes'],
        ['url' => 'clientes/listar.php', 'icon' => 'bi-people', 'label' => 'Clientes', 'permiso' => 'clientes'],
        ['url' => 'equipos/listar.php', 'icon' => 'bi-laptop', 'label' => 'Equipos', 'permiso' => 'equipos'],
        ['url' => 'inventario/listar.php', 'icon' => 'bi-box-seam', 'label' => 'Inventario', 'permiso' => 'inventario'],
        ['url' => 'facturacion/listar.php', 'icon' => 'bi-receipt', 'label' => 'Facturación', 'permiso' => 'facturacion'],
        ['url' => 'presupuestos/listar.php', 'icon' => 'bi-file-earmark-text', 'label' => 'Presupuestos', 'permiso' => 'presupuestos'],
        ['url' => 'cotizaciones/listar.php', 'icon' => 'bi-file-earmark-ruled', 'label' => 'Cotizaciones', 'permiso' => 'cotizaciones'],
        ['url' => 'garantias/listar.php', 'icon' => 'bi-shield-check', 'label' => 'Garantías', 'permiso' => 'garantias'],
        ['url' => 'agenda/index.php', 'icon' => 'bi-calendar-check', 'label' => 'Agenda', 'permiso' => 'agenda'],
        ['url' => 'reportes/index.php', 'icon' => 'bi-bar-chart', 'label' => 'Reportes', 'permiso' => 'reportes'],
        ['url' => 'configuracion/email.php', 'icon' => 'bi-envelope-at', 'label' => 'Config Email', 'permiso' => 'configuracion'],
        ['url' => 'dashboard/notificaciones.php', 'icon' => 'bi-envelope', 'label' => 'Notificaciones', 'permiso' => 'notificaciones'],
    ];
    return array_filter($items, function($item) {
        return hasPermission($item['permiso']);
    });
}

function redirect($url) {
    if (strpos($url, 'http') === 0 || strpos($url, BASE_URL) === 0) {
        $fullUrl = $url;
    } else {
        $fullUrl = BASE_URL . $url;
    }
    
    if (headers_sent()) {
        echo "<script>location.href='$fullUrl';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$fullUrl'></noscript>";
    } else {
        header("Location: " . $fullUrl);
    }
    exit();
}

function formatMoney($amount) {
    return 'S/ ' . number_format($amount, 2);
}

function generateCodigoOrden() {
    $conn = getConnection();
    $date = date('Ymd');
    $baseNum = 1;
    
    while (true) {
        $codigo = 'SAT-' . $date . '-' . str_pad($baseNum, 3, '0', STR_PAD_LEFT);
        $check = $conn->query("SELECT id FROM ordenes_servicio WHERE codigo = '$codigo'");
        
        if ($check->num_rows === 0) {
            return $codigo;
        }
        
        $baseNum++;
        if ($baseNum > 999) return 'SAT-ERROR-001';
    }
}

function generateNumeroFactura() {
    $conn = getConnection();
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) as total FROM facturas WHERE numero_factura LIKE 'F-$year-%'");
    $row = $result->fetch_assoc();
    $num = ($row['total'] ?? 0) + 1;
    return 'F-' . $year . '-' . str_pad($num, 6, '0', STR_PAD_LEFT);
}

function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function getConfig($clave) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT valor FROM configuraciones WHERE clave = ?");
    $stmt->bind_param("s", $clave);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['valor'] ?? '';
}

function logNotification($cliente_id, $orden_id, $tipo, $canal, $mensaje) {
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO notificaciones (cliente_id, orden_id, tipo, canal, mensaje, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $cliente_id, $orden_id, $tipo, $canal, $mensaje);
    return $stmt->execute();
}

function sendNotification($cliente_id, $orden_id, $estado) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT c.nombre, c.email, c.telefono, o.codigo FROM clientes c JOIN ordenes_servicio o ON o.cliente_id = c.id WHERE o.id = ?");
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();
    if (!$cliente) return false;
    
    $estados_msg = [
        'recibido' => 'Su equipo ha sido recibido y será atendido a la brevedad.',
        'en_diagnostico' => 'Su equipo está en proceso de diagnóstico.',
        'en_reparacion' => 'La reparación de su equipo está en curso.',
        'esperando_repuestos' => 'Su equipo necesita repuestos.',
        'reparado' => '¡Su equipo ha sido reparado! Puede pasar a retirarlo.',
        'entregado' => 'Gracias por confiar en nuestro servicio.'
    ];
    
    $mensaje = "Hola {$cliente['nombre']}, le informamos que su equipo (Orden: {$cliente['codigo']}) ahora está: " . ($estados_msg[$estado] ?? $estado);
    logNotification($cliente_id, $orden_id, 'estado', 'whatsapp', $mensaje);
    return true;
}
?>