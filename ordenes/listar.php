<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../SistemaSAT/autenticacion/login.php');
}

$page_title = 'Órdenes de Servicio';
$estado = $_GET['estado'] ?? '';
$search = $_GET['search'] ?? '';
$ordenes = getAllOrdenes($estado, $search);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-ticket-detailed"></i> Órdenes de Servicio</h1>
        <a href="agregar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Orden
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <?php foreach (ESTADOS_ORDEN as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $estado === $key ? 'selected' : '' ?>><?= $value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por código, cliente, equipo..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Equipo</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Técnico</th>
                            <th>Fecha Ingreso</th>
                            <th>Costo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($orden = $ordenes->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $orden['codigo'] ?></strong></td>
                            <td><?= htmlspecialchars($orden['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) ?></td>
                            <td>
                                <span class="badge" style="background-color: <?= COLORES_ESTADO[$orden['estado']] ?? '#6c757d' ?>">
                                    <?= ESTADOS_ORDEN[$orden['estado']] ?? $orden['estado'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= ($orden['prioridad'] ?? 'normal') === 'urgente' ? 'danger' : (($orden['prioridad'] ?? 'normal') === 'alta' ? 'warning' : 'secondary') ?>">
                                    <?= PRIORIDADES[$orden['prioridad'] ?? 'normal'] ?? ($orden['prioridad'] ?? 'normal') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($orden['fecha_ingreso'])) ?></td>
                            <td><?= formatMoney($orden['costo_total']) ?></td>
                            <td>
                                <a href="ver.php?id=<?= $orden['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="cambiar_estado.php?id=<?= $orden['id'] ?>" class="btn btn-sm btn-outline-info" title="Cambiar Estado">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($ordenes->num_rows == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No se encontraron órdenes</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>