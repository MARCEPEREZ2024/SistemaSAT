<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';

session_start();

if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $conn = getConnection();
} catch (Exception $e) {
    http_response_code(500);
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

function sendEvent($type, $data) {
    echo "event: $type\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if (function_exists('ob_flush') && function_exists('flush')) {
        @ob_flush();
        @flush();
    }
}

$start_time = time();
$timeout = 300; // 5 minutes

echo ": connected\n\n";
if (function_exists('ob_flush')) {
    @ob_flush();
}
@flush();

while ((time() - $start_time) < $timeout) {
    try {
        $stmt = $conn->prepare("
            SELECT n.id, n.tipo, n.mensaje, n.link, n.fecha_creacion, n.leida
            FROM notificaciones n
            WHERE n.id > ? AND (n.usuario_id = ? OR n.usuario_id IS NULL)
            ORDER BY n.fecha_creacion DESC
            LIMIT 10
        ");
        
        if (!$stmt) {
            sleep(2);
            continue;
        }
        
        $stmt->bind_param("ii", $last_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $notificaciones = [];
            while ($row = $result->fetch_assoc()) {
                $notificaciones[] = $row;
                $last_id = max($last_id, (int)$row['id']);
            }
            sendEvent('notificaciones', $notificaciones);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
    
    sleep(3);
}