<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/audit_helper.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

if (!isAdmin()) {
    redirect('../dashboard/index.php');
}

$page_title = 'Audit Trail';
$conn = getConnection();

$tableExists = $conn->query("SHOW TABLES LIKE 'audit_log'")->num_rows > 0;

if (!$tableExists) {
    echo '<div class="container-fluid p-4"><div class="alert alert-warning">';
    echo '<h4>Tabla no encontrada</h4>';
    echo '<p>Debe crear la tabla <code>audit_log</code> en la base de datos:</p>';
    echo '<pre class="bg-dark text-light p-3 rounded">CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    usuario_nombre VARCHAR(100),
    accion VARCHAR(50) NOT NULL,
    tabla VARCHAR(50),
    registro_id INT,
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    ip VARCHAR(45),
    user_agent VARCHAR(255),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_tabla (tabla),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>';
    echo '</div></div>';
    require_once '../include/footer.php';
    exit;
}

$pagina = (int)($_GET['page'] ?? 1);
$filtros = [
    'usuario_id' => !empty($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : null,
    'accion' => $_GET['accion'] ?? null,
    'tabla' => $_GET['tabla'] ?? null,
    'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
    'fecha_fin' => $_GET['fecha_fin'] ?? null
];

$resultado = getAuditLog($conn, $filtros, $pagina, 50);
$stats = getEstadisticasAudit($conn, 30);

$usuarios = $conn->query("SELECT id, nombre FROM usuarios ORDER BY nombre");

require_once '../include/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-journal-text"></i> Audit Trail</h1>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Actividades (30 días)</div>
                <div class="card-body">
                    <?php if (!empty($stats['acciones'])): ?>
                        <?php foreach ($stats['acciones'] as $accion => $total): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?= htmlspecialchars($accion) ?></span>
                            <span class="badge bg-primary"><?= $total ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Sin datos</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Tablas afectadas</div>
                <div class="card-body">
                    <?php if (!empty($stats['tablas'])): ?>
                        <?php foreach ($stats['tablas'] as $tabla => $total): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?= htmlspecialchars($tabla) ?></span>
                            <span class="badge bg-success"><?= $total ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Sin datos</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Usuarios más activos</div>
                <div class="card-body">
                    <?php if (!empty($stats['usuarios'])): ?>
                        <?php foreach ($stats['usuarios'] as $usuario => $total): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?= htmlspecialchars($usuario) ?></span>
                            <span class="badge bg-info"><?= $total ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Sin datos</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Usuario</label>
                    <select name="usuario_id" class="form-select">
                        <option value="">Todos</option>
                        <?php while ($u = $usuarios->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>" <?= $filtros['usuario_id']==$u['id']?'selected':'' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Acción</label>
                    <select name="accion" class="form-select">
                        <option value="">Todas</option>
                        <option value="crear" <?= $filtros['accion']=='crear'?'selected':'' ?>>Crear</option>
                        <option value="actualizar" <?= $filtros['accion']=='actualizar'?'selected':'' ?>>Actualizar</option>
                        <option value="eliminar" <?= $filtros['accion']=='eliminar'?'selected':'' ?>>Eliminar</option>
                        <option value="login_exitoso" <?= $filtros['accion']=='login_exitoso'?'selected':'' ?>>Login exitoso</option>
                        <option value="login_fallido" <?= $filtros['accion']=='login_fallido'?'selected':'' ?>>Login fallido</option>
                        <option value="logout" <?= $filtros['accion']=='logout'?'selected':'' ?>>Logout</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tabla</label>
                    <select name="tabla" class="form-select">
                        <option value="">Todas</option>
                        <option value="ordenes_servicio" <?= $filtros['tabla']=='ordenes_servicio'?'selected':'' ?>>Órdenes</option>
                        <option value="clientes" <?= $filtros['tabla']=='clientes'?'selected':'' ?>>Clientes</option>
                        <option value="usuarios" <?= $filtros['tabla']=='usuarios'?'selected':'' ?>>Usuarios</option>
                        <option value="repuestos" <?= $filtros['tabla']=='repuestos'?'selected':'' ?>>Inventario</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= $filtros['fecha_inicio'] ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= $filtros['fecha_fin'] ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Tabla</th>
                            <th>ID Registro</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultado['registros'] as $r): ?>
                        <tr>
                            <td><small><?= date('d/m/Y H:i:s', strtotime($r['fecha'])) ?></small></td>
                            <td><?= htmlspecialchars($r['usuario_nombre'] ?? 'Sistema') ?></td>
                            <td>
                                <?php 
                                $badgeClass = match($r['accion']) {
                                    'crear' => 'success',
                                    'actualizar' => 'warning',
                                    'eliminar' => 'danger',
                                    'login_exitoso' => 'info',
                                    'login_fallido' => 'danger',
                                    'logout' => 'secondary',
                                    default => 'primary'
                                };
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($r['accion']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($r['tabla'] ?? '-') ?></td>
                            <td><?= $r['registro_id'] ?? '-' ?></td>
                            <td><small><?= htmlspecialchars($r['ip']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($resultado['total_paginas'] > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $resultado['total_paginas']; $i++): ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>