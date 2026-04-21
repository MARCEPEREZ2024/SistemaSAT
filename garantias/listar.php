<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Garantías';

$sql = "SELECT g.*, o.codigo as orden_codigo, e.marca, e.modelo, c.nombre as cliente_nombre, 
        DATEDIFF(g.fecha_fin, CURDATE()) as dias_restantes
        FROM garantias g 
        JOIN ordenes_servicio o ON g.orden_id = o.id 
        LEFT JOIN equipos e ON o.equipo_id = e.id
        LEFT JOIN clientes c ON o.cliente_id = c.id
        ORDER BY g.fecha_fin ASC";

$garantias = $conn->query($sql);

$sql_vencidas = "SELECT g.*, o.codigo as orden_codigo, c.nombre as cliente_nombre 
                 FROM garantias g 
                 JOIN ordenes_servicio o ON g.orden_id = o.id 
                 LEFT JOIN clientes c ON o.cliente_id = c.id
                 WHERE g.estado = 'activa' AND g.fecha_fin < CURDATE()";
$vencidas_result = $conn->query($sql_vencidas);
while ($v = $vencidas_result->fetch_assoc()) {
    $conn->query("UPDATE garantias SET estado = 'vencida' WHERE id = " . $v['id']);
}

$vencidas = $conn->query("SELECT g.*, o.codigo as orden_codigo, c.nombre as cliente_nombre 
                          FROM garantias g 
                          JOIN ordenes_servicio o ON g.orden_id = o.id 
                          LEFT JOIN clientes c ON o.cliente_id = c.id
                          WHERE g.estado = 'vencida' ORDER BY g.fecha_fin DESC");

$activas = $conn->query("SELECT g.*, o.codigo as orden_codigo, c.nombre as cliente_nombre,
                         DATEDIFF(g.fecha_fin, CURDATE()) as dias_restantes
                         FROM garantias g 
                         JOIN ordenes_servicio o ON g.orden_id = o.id 
                         LEFT JOIN clientes c ON o.cliente_id = c.id
                         WHERE g.estado = 'activa' AND g.fecha_fin >= CURDATE()
                         ORDER BY g.fecha_fin ASC");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-shield-check"></i> Garantías</h1>
    </div>
    
    <?php if ($vencidas->num_rows > 0): ?>
    <div class="alert alert-danger mb-4">
        <i class="bi bi-exclamation-triangle"></i> 
        <strong><?= $vencidas->num_rows ?></strong> garantía(s) vencida(s)
    </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Garantías Activas</h5>
                    <h2><?= $activas->num_rows ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Garantías Vencidas</h5>
                    <h2><?= $vencidas->num_rows ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Total Garantías</h5>
                    <h2><?= $garantias->num_rows ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($vencidas->num_rows > 0): ?>
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Garantías Vencidas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($g = $vencidas->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $g['orden_codigo'] ?></strong></td>
                            <td><?= htmlspecialchars($g['cliente_nombre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($g['fecha_inicio'])) ?></td>
                            <td class="text-danger"><?= date('d/m/Y', strtotime($g['fecha_fin'])) ?></td>
                            <td>
                                <a href="../ordenes/ver.php?id=<?= $g['orden_id'] ?>" class="btn btn-sm btn-outline-primary">Ver Orden</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Todas las Garantías</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Equipo</th>
                            <th>Meses</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Días Rest.</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($g = $garantias->fetch_assoc()): 
                            $dias = $g['dias_restantes'] ?? 0;
                            $estado = $g['estado'];
                            if ($estado === 'activa' && $dias < 0) {
                                $estado = 'vencida';
                            }
                        ?>
                        <tr>
                            <td><strong><?= $g['orden_codigo'] ?></strong></td>
                            <td><?= htmlspecialchars($g['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars(($g['marca'] ?? '') . ' ' . ($g['modelo'] ?? '')) ?></td>
                            <td><?= $g['meses'] ?> meses</td>
                            <td><?= date('d/m/Y', strtotime($g['fecha_inicio'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($g['fecha_fin'])) ?></td>
                            <td>
                                <?php if ($estado === 'activa'): ?>
                                    <?php if ($dias <= 30): ?>
                                        <span class="badge bg-warning"><?= $dias ?> días</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= $dias ?> días</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $badgeClass = match($estado) {
                                    'activa' => 'success',
                                    'vencida' => 'danger',
                                    'usada' => 'secondary',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($estado) ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($garantias->num_rows == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No hay garantías registradas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>