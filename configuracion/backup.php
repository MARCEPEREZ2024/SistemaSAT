<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/backup_helper.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

if (!isAdmin()) {
    redirect('../dashboard/index.php');
}

$page_title = 'Backup';
$conn = getConnection();
$error = '';
$success = '';

$backupDir = dirname(__DIR__) . '/backups';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'crear_backup') {
            $result = realizarBackup($conn, $backupDir);
            if ($result['success']) {
                $success = 'Backup creado: ' . $result['filename'] . ' (' . number_format($result['size']/1024, 1) . ' KB)';
            } else {
                $error = $result['error'] ?? 'Error al crear backup';
            }
        } elseif ($_POST['action'] === 'restaurar') {
            $archivo = basename($_POST['archivo']);
            $filepath = $backupDir . '/' . $archivo;
            $result = restaurarBackup($conn, $filepath);
            if ($result['success']) {
                $success = 'Base de datos restaurada correctamente';
            } else {
                $error = $result['error'];
            }
        } elseif ($_POST['action'] === 'eliminar') {
            $archivo = basename($_POST['archivo']);
            $filepath = $backupDir . '/' . $archivo;
            if (eliminarBackup($filepath)) {
                $success = 'Backup eliminado';
            } else {
                $error = 'Error al eliminar';
            }
        } elseif ($_POST['action'] === 'guardar_config') {
            $config = [
                'auto_backup' => isset($_POST['auto_backup']),
                'frecuencia' => $_POST['frecuencia'] ?? 'diario',
                'hora' => $_POST['hora'] ?? '02:00',
                'retener' => (int)($_POST['retener'] ?? 7),
                'email_notif' => isset($_POST['email_notif'])
            ];
            saveBackupSettings($conn, $config);
            $success = 'Configuración guardada';
        }
    }
}

$backups = listarBackups($backupDir);
$config = getBackupSettings($conn);

require_once '../include/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-hdd-drive"></i> Backup y Restauración</h1>
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="crear_backup">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Crear Backup Ahora
            </button>
        </form>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Backups Disponibles</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($backups)): ?>
                        <p class="text-muted">No hay backups disponibles</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $b): ?>
                                    <tr>
                                        <td><i class="bi bi-file-zip"></i> <?= htmlspecialchars($b['filename']) ?></td>
                                        <td><?= number_format($b['size']/1024, 1) ?> KB</td>
                                        <td><?= date('d/m/Y H:i', $b['modified']) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Restaurar este backup? Se perderán los datos actuales.');">
                                                    <input type="hidden" name="action" value="restaurar">
                                                    <input type="hidden" name="archivo" value="<?= htmlspecialchars($b['filename']) ?>">
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este backup?');">
                                                    <input type="hidden" name="action" value="eliminar">
                                                    <input type="hidden" name="archivo" value="<?= htmlspecialchars($b['filename']) ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Configuración Automática</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="guardar_config">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup" <?= $config['auto_backup'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="auto_backup">Habilitar backup automático</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frecuencia</label>
                            <select name="frecuencia" class="form-select">
                                <option value="horario" <?= $config['frecuencia']=='horario'?'selected':'' ?>>Cada hora</option>
                                <option value="diario" <?= $config['frecuencia']=='diario'?'selected':'' ?>>Diario</option>
                                <option value="semanal" <?= $config['frecuencia']=='semanal'?'selected':'' ?>>Semanal</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hora</label>
                            <input type="time" name="hora" class="form-control" value="<?= $config['hora'] ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">保留备份数</label>
                            <input type="number" name="retener" class="form-control" value="<?= $config['retener'] ?>" min="1" max="30">
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="email_notif" name="email_notif" <?= $config['email_notif'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notif">Notificar por email</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-1">Para automatizar los backups, configure una tarea cron:</p>
                    <code class="d-block p-2 bg-light rounded small">
                        0 2 * * * php <?= dirname(__DIR__) ?>/cron/backup.php
                    </code>
                    <p class="small text-muted mt-2 mb-0">O en Windows usar el Programador de tareas</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>