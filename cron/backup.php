<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/backup_helper.php';
require_once __DIR__ . '/../include/email_helper.php';

$conn = getConnection();
$config = getBackupSettings($conn);

if (!$config['auto_backup']) {
    echo "Auto backup deshabilitado\n";
    exit;
}

$ultimoBackup = @file_get_contents(__DIR__ . '/.last_backup');
$ahora = time();
$horaActual = date('H:i');

$frecuenciaValida = false;

switch ($config['frecuencia']) {
    case 'horario':
        if (!$ultimoBackup || ($ahora - $ultimoBackup) >= 3600) {
            $frecuenciaValida = true;
        }
        break;
    case 'diario':
        if ($horaActual >= $config['hora'] && (!$ultimoBackup || date('Y-m-d', $ultimoBackup) != date('Y-m-d'))) {
            $frecuenciaValida = true;
        }
        break;
    case 'semanal':
        if (date('w') == 0 && $horaActual >= $config['hora']) {
            $frecuenciaValida = true;
        }
        break;
}

if (!$frecuenciaValida) {
    echo "No es momento de hacer backup\n";
    exit;
}

$backupDir = dirname(__DIR__) . '/backups';
$result = realizarBackup($conn, $backupDir);

if ($result['success']) {
    file_put_contents(__DIR__ . '/.last_backup', $ahora);
    
    if ($config['email_notif']) {
        $mensaje = "Backup automático completado:\n";
        $mensaje .= "Archivo: " . $result['filename'] . "\n";
        $mensaje .= "Tamaño: " . number_format($result['size']/1024, 1) . " KB\n";
        $mensaje .= "Fecha: " . date('Y-m-d H:i:s');
        
        $admins = $conn->query("SELECT email FROM usuarios WHERE rol = 'admin' AND estado = 'activo'");
        while ($admin = $admins->fetch_assoc()) {
            enviarEmail($admin['email'], 'Backup Automático - Sistema SAT', $mensaje);
        }
    }
    
    limpiarBackupsAntiguos($backupDir, $config['retener']);
    
    echo "Backup creado: " . $result['filename'] . "\n";
} else {
    echo "Error: " . $result['error'] . "\n";
}

function limpiarBackupsAntiguos($dir, $retener) {
    $backups = glob($dir . '/*.sql.gz');
    if (count($backups) <= $retener) return;
    
    usort($backups, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    $eliminar = count($backups) - $retener;
    for ($i = 0; $i < $eliminar; $i++) {
        @unlink($backups[$i]);
    }
}