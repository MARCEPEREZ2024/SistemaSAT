<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/include/funciones.php';
require_once __DIR__ . '/include/logger_helper.php';
require_once __DIR__ . '/include/pagination_helper.php';
require_once __DIR__ . '/include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

if (!is_admin()) {
    redirect('dashboard/index.php');
}

$page_title = 'Logs de Actividad';

$entidad = $_GET['entidad'] ?? '';
$usuario_id = $_GET['usuario_id'] ?? 0;
$accion = $_GET['accion'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$conn = getConnection();

$sql = "SELECT a.*, u.nombre as usuario_nombre 
        FROM actividades a 
        LEFT JOIN usuarios u ON a.usuario_id = u.id 
        WHERE 1=1";

$params = [];
$types = '';

if ($entidad) {
    $sql .= " AND a.entidad = ?";
    $params[] = $entidad;
    $types .= 's';
}

if ($usuario_id > 0) {
    $sql .= " AND a.usuario_id = ?";
    $params[] = $usuario_id;
    $types .= 'i';
}

if ($accion) {
    $sql .= " AND a.accion = ?";
    $params[] = $accion;
    $types .= 's';
}

if ($fecha_inicio) {
    $fi = date('Y-m-d', strtotime($fecha_inicio));
    $sql .= " AND a.fecha >= '$fi'";
}

if ($fecha_fin) {
    $ff = date('Y-m-d', strtotime($fecha_fin)) . ' 23:59:59';
    $sql .= " AND a.fecha <= '$ff'";
}

$sql .= " ORDER BY a.fecha DESC LIMIT 200";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $actividades = $stmt->get_result();
} else {
    $actividades = $conn->query($sql);
}

$usuarios = $conn->query("SELECT id, nombre FROM usuarios ORDER BY nombre");
$acciones = $conn->query("SELECT DISTINCT accion FROM actividades ORDER BY accion");
$entidades = $conn->query("SELECT DISTINCT entidad FROM actividades WHERE entidad != '' ORDER BY entidad");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-clock-history"></i> Logs de Actividad</h1>
        <a href="?page=dashboard" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Usuario</label>
                    <select name="usuario_id" class="form-select">
                        <option value="0">Todos</option>
                        <?php while ($u = $usuarios->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>" <?= $usuario_id == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Entidad</label>
                    <select name="entidad" class="form-select">
                        <option value="">Todas</option>
                        <?php while ($e = $entidades->fetch_assoc()): ?>
                        <option value="<?= $e['entidad'] ?>" <?= $entidad === $e['entidad'] ? 'selected' : '' ?>><?= htmlspecialchars($e['entidad']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Acción</label>
                    <select name="accion" class="form-select">
                        <option value="">Todas</option>
                        <?php while ($a = $acciones->fetch_assoc()): ?>
                        <option value="<?= $a['accion'] ?>" <?= $accion === $a['accion'] ? 'selected' : '' ?>><?= htmlspecialchars($a['accion']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Entidad</th>
                            <th>Detalles</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($a = $actividades->fetch_assoc()): ?>
                        <tr>
                            <td><small><?= date('d/m H:i', strtotime($a['fecha'])) ?></small></td>
                            <td><?= htmlspecialchars($a['usuario_nombre'] ?? 'Sistema') ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $a['accion'] === 'create' ? 'success' : 
                                    ($a['accion'] === 'update' ? 'warning' : 
                                    ($a['accion'] === 'delete' ? 'danger' : 'secondary')) 
                                ?>"><?= $a['accion'] ?></span>
                            </td>
                            <td><?= htmlspecialchars($a['entidad'] ?: '-') ?></td>
                            <td><small><?= htmlspecialchars($a['detalles'] ?? '-') ?></small></td>
                            <td><small class="text-muted"><?= $a['ip'] ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($actividades->num_rows == 0): ?>
                        <tr><td colspan="6" class="text-center text-muted p-4">Sin actividad registrada</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>