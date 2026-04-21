<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$page_title = 'Dashboard';
$tecnico_id = $_GET['tecnico_id'] ?? 0;

$tecnicos = getAllUsers();

$stats = getEstadisticas();
$repuestos_bajo = getRepuestosStockBajo();

if ($tecnico_id > 0) {
    $ordenes_tecnico = $conn->query("SELECT o.*, c.nombre as cliente_nombre, e.marca, e.modelo FROM ordenes_servicio o LEFT JOIN clientes c ON o.cliente_id = c.id LEFT JOIN equipos e ON o.equipo_id = e.id WHERE o.tecnico_id = $tecnico_id AND o.estado_orden = 'abierta' ORDER BY o.fecha_ingreso DESC");
    
    $stats_tecnico = [
        'abiertas' => $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE tecnico_id = $tecnico_id AND estado_orden = 'abierta'")->fetch_assoc()['total'],
        'en_proceso' => $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE tecnico_id = $tecnico_id AND estado IN ('en_diagnostico', 'en_reparacion', 'esperando_repuestos')")->fetch_assoc()['total'],
        'completadas' => $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE tecnico_id = $tecnico_id AND estado_orden = 'cerrada' AND MONTH(fecha_reparacion) = MONTH(CURRENT_DATE)")->fetch_assoc()['total']
    ];
}
?>
<div class="container-fluid">
    <h1 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h1>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Filtrar por Técnico</label>
                            <select name="tecnico_id" class="form-select" onchange="this.form.submit()">
                                <option value="0">Todos los técnicos</option>
                                <?php while ($t = $tecnicos->fetch_assoc()): ?>
                                <option value="<?= $t['id'] ?>" <?= $tecnico_id == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($tecnico_id > 0): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Mis Órdenes Abiertas</h6>
                    <h2><?= $stats_tecnico['abiertas'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-title">En Proceso</h6>
                    <h2><?= $stats_tecnico['en_proceso'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Completadas Este Mes</h6>
                    <h2><?= $stats_tecnico['completadas'] ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Órdenes Asignadas a este Técnico</h5>
        </div>
        <div class="card-body">
            <?php if ($ordenes_tecnico && $ordenes_tecnico->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Equipo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($orden = $ordenes_tecnico->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $orden['codigo'] ?></strong></td>
                            <td><?= htmlspecialchars($orden['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) ?></td>
                            <td>
                                <span class="badge" style="background-color: <?= COLORES_ESTADO[$orden['estado']] ?? '#6c757d' ?>">
                                    <?= ESTADOS_ORDEN[$orden['estado']] ?? $orden['estado'] ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($orden['fecha_ingreso'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>ordenes/ver.php?id=<?= $orden['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted">No hay órdenes asignadas</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($tecnico_id == 0): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Órdenes Abiertas</h6>
                            <h2 class="mb-0"><?= $stats['ordenes_abiertas'] ?></h2>
                        </div>
                        <i class="bi bi-ticket-detailed" style="font-size: 3rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Ingresos del Mes</h6>
                            <h2 class="mb-0"><?= formatMoney($stats['ingresos_mes']) ?></h2>
                        </div>
                        <i class="bi bi-cash-stack" style="font-size: 3rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Clientes Nuevos</h6>
                            <h2 class="mb-0"><?= $stats['clientes_nuevos'] ?></h2>
                        </div>
                        <i class="bi bi-people" style="font-size: 3rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Rep. Stock Bajo</h6>
                            <h2 class="mb-0"><?= $repuestos_bajo->num_rows ?></h2>
                        </div>
                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-ticket-detailed"></i> Órdenes Recientes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Equipo</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['ordenes_recientes'] as $orden): ?>
                                <tr>
                                    <td><strong><?= $orden['codigo'] ?></strong></td>
                                    <td><?= htmlspecialchars($orden['cliente_nombre']) ?></td>
                                    <td><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?= COLORES_ESTADO[$orden['estado']] ?? '#6c757d' ?>">
                                            <?= ESTADOS_ORDEN[$orden['estado']] ?? $orden['estado'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($orden['fecha_ingreso'])) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>ordenes/ver.php?id=<?= $orden['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0 text-dark"><i class="bi bi-exclamation-triangle"></i> Stock Bajo</h5>
                </div>
                <div class="card-body">
                    <?php if ($repuestos_bajo->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($rep = $repuestos_bajo->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($rep['nombre']) ?>
                                <span class="badge bg-danger"><?= $rep['stock'] ?></span>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                        <a href="<?= BASE_URL ?>inventario/listar.php?alerta=1" class="btn btn-warning btn-sm mt-3 w-100">
                            <i class="bi bi-box-seam"></i> Ver Inventario
                        </a>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0"><i class="bi bi-check-circle"></i> Todo en orden</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Estados de Órdenes</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($stats['ordenes'] as $estado): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><?= ESTADOS_ORDEN[$estado['estado']] ?? $estado['estado'] ?></span>
                            <span class="badge" style="background-color: <?= COLORES_ESTADO[$estado['estado']] ?? '#6c757d' ?>">
                                <?= $estado['total'] ?>
                            </span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: <?= ($estado['total'] / max($stats['ordenes_abiertas'], 1)) * 100 ?>%; background-color: <?= COLORES_ESTADO[$estado['estado']] ?? '#6c757d' ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once '../include/footer.php'; ?>