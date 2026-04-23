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

$action = $_GET['action'] ?? '';
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit();
}

try {
    $conn = getConnection();
    $search = "%" . $conn->real_escape_string($query) . "%";
    $results = [];

    switch ($action) {
        case 'ordenes':
            $stmt = $conn->prepare("
                SELECT o.id, o.codigo, o.estado, c.nombre as cliente, e.marca, e.modelo 
                FROM ordenes_servicio o 
                LEFT JOIN clientes c ON o.cliente_id = c.id 
                LEFT JOIN equipos e ON o.equipo_id = e.id 
                WHERE o.codigo LIKE ? OR c.nombre LIKE ? OR e.marca LIKE ? 
                ORDER BY o.fecha_ingreso DESC LIMIT 10
            ");
            $stmt->bind_param("sss", $search, $search, $search);
            $stmt->execute();
            $data = $stmt->get_result();
            while ($row = $data->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'text' => $row['codigo'] . ' - ' . $row['cliente'] . ' (' . $row['marca'] . ' ' . $row['modelo'] . ')',
                    'estado' => $row['estado']
                ];
            }
            break;
            
        case 'clientes':
            $stmt = $conn->prepare("
                SELECT id, nombre, email, telefono 
                FROM clientes 
                WHERE nombre LIKE ? OR email LIKE ? OR telefono LIKE ? OR dni LIKE ?
                ORDER BY nombre ASC LIMIT 10
            ");
            $stmt->bind_param("ssss", $search, $search, $search, $search);
            $stmt->execute();
            $data = $stmt->get_result();
            while ($row = $data->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'text' => $row['nombre'] . ' - ' . ($row['telefono'] ?? '')
                ];
            }
            break;
            
        case 'inventario':
            $stmt = $conn->prepare("
                SELECT id, codigo, nombre, stock, precio_venta 
                FROM repuestos 
                WHERE estado = 'activo' AND (codigo LIKE ? OR nombre LIKE ? OR categoria LIKE ?) 
                ORDER BY nombre ASC LIMIT 10
            ");
            $stmt->bind_param("sss", $search, $search, $search);
            $stmt->execute();
            $data = $stmt->get_result();
            while ($row = $data->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'text' => $row['codigo'] . ' - ' . $row['nombre'] . ' (Stock: ' . $row['stock'] . ')'
                ];
            }
            break;
            
        case 'equipos':
            $stmt = $conn->prepare("
                SELECT e.id, e.marca, e.modelo, e.serie, c.nombre as cliente 
                FROM equipos e 
                LEFT JOIN clientes c ON e.cliente_id = c.id 
                WHERE e.marca LIKE ? OR e.modelo LIKE ? OR e.serie LIKE ? 
                ORDER BY e.fecha_ingreso DESC LIMIT 10
            ");
            $stmt->bind_param("sss", $search, $search, $search);
            $stmt->execute();
            $data = $stmt->get_result();
            while ($row = $data->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'text' => $row['marca'] . ' ' . $row['modelo'] . ' - ' . $row['serie']
                ];
            }
            break;
    }
    
    echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}