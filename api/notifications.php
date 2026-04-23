<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/include/chat_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
        case 'mark_all_read':
        case 'delete':
            echo json_encode(['success' => true]);
            break;
        default:
            echo json_encode(['error' => 'Acción no válida']);
    }
    exit();
}

$conn = getConnection();

$notificaciones = [];
$no_leidas = 0;

try {
    $stmt = $conn->prepare("SELECT id, titulo, mensaje, tipo, link, fecha, leida FROM notificaciones WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 20");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
        if (empty($row['leida'])) $no_leidas++;
    }
    $stmt->close();
} catch (Exception $e) {
    $no_leidas = 0;
}

$chat_no_leidos = 0;
try {
    if (function_exists('getMensajesNoLeidos')) {
        $chat_no_leidos = getMensajesNoLeidos($conn, $usuario_id);
    }
} catch (Exception $e) {
    $chat_no_leidos = 0;
}

$no_leidas += $chat_no_leidos;

if ($chat_no_leidos > 0) {
    array_unshift($notificaciones, [
        'id' => 'chat-' . $usuario_id,
        'titulo' => 'Chat',
        'mensaje' => 'Tienes ' . $chat_no_leidos . ' mensaje(s) sin leer en el chat',
        'link' => BASE_URL . 'chat/index.php',
        'fecha' => date('Y-m-d H:i:s'),
        'leida' => 0
    ]);
}

echo json_encode([
    'total' => count($notificaciones),
    'no_leidas' => $no_leidas,
    'notificaciones' => $notificaciones
]);