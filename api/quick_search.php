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

$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $conn = getConnection();
    $search = "%" . $conn->real_escape_string($query) . "%";
    $results = [];

    switch ($type) {
        case 'ordenes':
            $stmt = $conn->prepare("SELECT o.id, CONCAT(o.codigo, ' - ', c.nombre) as text FROM ordenes_servicio o LEFT JOIN clientes c ON o.cliente_id = c.id WHERE o.codigo LIKE ? OR c.nombre LIKE ? ORDER BY o.fecha_ingreso DESC LIMIT 5");
            $stmt->bind_param("ss", $search, $search);
            $stmt->execute();
            $data = $stmt->get_result();
            while ($row = $data->fetch_assoc()) {
                $results[] = $row;
            }
            break;
            
        case 'clientes':
            $stmt = $conn->prepare("SELECT id, CONCAT(nombre, ' - ', telefono) as text FROM clientes WHERE nombre LIKE ? OR telefono LIKE ? OR email LIKE ? ORDER BY nombre ASC LIMIT 5");
            $stmt->bind_param("sss", $search, $search, $search);
            $stmt->execute();
            $data = $stmt->get_result();
            while ($row = $data->fetch_assoc()) {
                $results[] = $row;
            }
            break;
            
        default:
            $stmt = $conn->prepare("SELECT o.id, CONCAT(o.codigo, ' - ', c.nombre) as text FROM ordenes_servicio o LEFT JOIN clientes c ON o.cliente_id = c.id WHERE o.codigo LIKE ? OR c.nombre LIKE ? ORDER BY o.fecha_ingreso DESC LIMIT 5");
            $stmt->bind_param("ss", $search, $search);
            $stmt->execute();
            $data = $stmt->get_result();
            while ($row = $data->fetch_assoc()) {
                $results[] = $row;
            }
    }
    
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}