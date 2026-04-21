<?php
require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

$cliente_id = $_GET['cliente_id'] ?? 0;

if ($cliente_id) {
    $stmt = $conn->prepare("SELECT * FROM equipos WHERE cliente_id = ? AND estado = 'activo' ORDER BY fecha_ingreso DESC");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $equipos = [];
    while ($row = $result->fetch_assoc()) {
        $equipos[] = $row;
    }
    
    echo json_encode($equipos);
} else {
    echo json_encode([]);
}
?>