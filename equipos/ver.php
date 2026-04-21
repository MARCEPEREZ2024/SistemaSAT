<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Ver Equipo';
$id = $_GET['id'] ?? 0;

$equipo = getEquipoById($id);
if (!$equipo) {
    redirect('equipos/listar.php');
}

$ordenes = $conn->query("SELECT o.*, u.nombre as tecnico_nombre FROM ordenes_servicio o LEFT JOIN usuarios u ON o.tecnico_id = u.id WHERE o.equipo_id = $id ORDER BY o.fecha_ingreso DESC");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-laptop"></i> <?= htmlspecialchars($equipo['marca'] . ' ' . $equipo['modelo']) ?></h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos del Equipo</h5>
                </div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($equipo['cliente_nombre']) ?></p>
                    <p><strong>Tipo:</strong> <?= ucfirst($equipo['tipo_equipo']) ?></p>
                    <p><strong>Marca:</strong> <?= htmlspecialchars($equipo['marca']) ?></p>
                    <p><strong>Modelo:</strong> <?= htmlspecialchars($equipo['modelo'] ?? '-') ?></p>
                    <p><strong>Serie:</strong> <?= htmlspecialchars($equipo['serie'] ?? '-') ?></p>
                    <p><strong>Estado Físico:</strong> 
                        <span class="badge bg-<?= $equipo['estado_equipo'] === 'bueno' ? 'success' : ($equipo['estado_equipo'] === 'regular' ? 'warning' : 'danger') ?>">
                            <?= ucfirst($equipo['estado_equipo']) ?>
                        </span>
                    </p>
                    <p><strong>Fecha Ingreso:</strong> <?= date('d/m/Y', strtotime($equipo['fecha_ingreso'])) ?></p>
                    <a href="editar.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Contraseñas</h5>
                </div>
                <div class="card-body">
                    <p><strong>BIOS:</strong> <?= htmlspecialchars($equipo['passwordBIOS'] ?? '-') ?></p>
                    <p><strong>Sistema:</strong> <?= htmlspecialchars($equipo['passwordSO'] ?? '-') ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-box"></i> Accesorios</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= htmlspecialchars($equipo['accesorios'] ?? 'Sin accesorios') ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-journal-text"></i> Diagnóstico Inicial</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= htmlspecialchars($equipo['diagnostico_inicial'] ?? 'Sin diagnóstico inicial') ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-ticket-detailed"></i> Historial de Órdenes</h5>
                    <a href="../ordenes/agregar.php?equipo_id=<?= $id ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Nueva Orden
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($ordenes->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Técnico</th>
                                        <th>Estado</th>
                                        <th>Costo</th>
                                        <th>Fecha Ingreso</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ord = $ordenes->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= $ord['codigo'] ?></strong></td>
                                        <td><?= htmlspecialchars($ord['tecnico_nombre'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge" style="background-color: <?= COLORES_ESTADO[$ord['estado']] ?? '#6c757d' ?>">
                                                <?= ESTADOS_ORDEN[$ord['estado']] ?? $ord['estado'] ?>
                                            </span>
                                        </td>
                                        <td><?= formatMoney($ord['costo_total']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($ord['fecha_ingreso'])) ?></td>
                                        <td>
                                            <a href="../ordenes/ver.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No hay órdenes de servicio</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>