<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

try {
    $conn = getConnection();
    
    switch ($type) {
        case 'general':
            $total = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio")->fetch_assoc()['t'] ?? 0;
            $pendientes = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio WHERE estado NOT IN ('entregado', 'cancelado')")->fetch_assoc()['t'] ?? 0;
            $entregadas = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio WHERE estado = 'entregado'")->fetch_assoc()['t'] ?? 0;
            $clientes = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado = 'activo'")->fetch_assoc()['t'] ?? 0;
            echo json_encode([
                'ordenes_total' => $total,
                'ordenes_pendientes' => $pendientes,
                'ordenes_entregadas' => $entregadas,
                'clientes_total' => $clientes
            ]);
            break;
            
        case 'estados':
            $result = $conn->query("SELECT estado, COUNT(*) as total FROM ordenes_servicio GROUP BY estado");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['estado']] = (int)$row['total'];
            }
            echo json_encode($data);
            break;
            
        case 'ultimas':
            $result = $conn->query("SELECT o.*, c.nombre as cliente_nombre, e.marca, e.modelo FROM ordenes_servicio o LEFT JOIN clientes c ON o.cliente_id = c.id LEFT JOIN equipos e ON o.equipo_id = e.id ORDER BY o.fecha_ingreso DESC LIMIT 10");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode($data);
            break;
            
        default:
            echo json_encode(['error' => 'Tipo no válido']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}