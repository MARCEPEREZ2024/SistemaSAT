<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();

if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$periodo = $_GET['period'] ?? 'month';
$anio = (int)($_GET['year'] ?? date('Y'));
$mes = (int)($_GET['month'] ?? date('n'));

try {
    $conn = getConnection();
    
    // Órdenes total
    $result = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio");
    $ordenes_total = $result ? $result->fetch_assoc()['t'] : 0;
    
    // Pendientes
    $result = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio WHERE estado NOT IN ('entregado', 'cancelado')");
    $pendientes = $result ? $result->fetch_assoc()['t'] : 0;
    
    // Clientes
    $result = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado = 'activo'");
    $clientes = $result ? $result->fetch_assoc()['t'] : 0;
    
    // Bajo stock
    $result = $conn->query("SELECT COUNT(*) as t FROM repuestos WHERE stock <= stock_minimo AND estado = 'activo'");
    $bajo_stock = $result ? $result->fetch_assoc()['t'] : 0;
    
    echo json_encode([
        'periodo' => $periodo,
        'ordenes' => ['total' => $ordenes_total, 'pendientes' => $pendientes],
        'clientes' => ['total' => $clientes],
        'inventario' => ['bajo_stock' => $bajo_stock]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}