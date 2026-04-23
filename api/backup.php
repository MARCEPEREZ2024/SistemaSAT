<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/include/backup_helper.php';
require_once __DIR__ . '/include/logger_helper.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit();
}

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solo administradores']);
    exit();
}

$action = $_GET['action'] ?? '';

$conn = getConnection();

switch ($action) {
    case 'create':
        log_actividad('create', 'backup', 0, 'Creó backup manual');
        descargar_backup('sat_backup_' . date('Ymd_His'));
        break;
        
    case 'list':
        $directorio = __DIR__ . '/../backups';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $backups = obtener_fechas_backups($directorio);
        echo json_encode($backups);
        break;
        
    case 'restore':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = $_POST['sql'] ?? '';
            if (empty($sql)) {
                echo json_encode(['error' => 'SQL requerido']);
                exit();
            }
            
            $result = restaurar_backup($sql, $conn);
            log_actividad('restore', 'backup', 0, 'Restauró backup: ' . ($result['exito'] ? 'éxito' : 'error'));
            echo json_encode($result);
        }
        break;
        
    case 'size':
        echo json_encode(['size' => obtener_size_db()]);
        break;
        
    default:
        echo json_encode(['error' => 'Acción no válida']);
}