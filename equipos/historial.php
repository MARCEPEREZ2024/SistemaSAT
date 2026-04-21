<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Historial de Equipo';
$serie = $_GET['serie'] ?? '';
$equipos = [];
$ordenes = [];

if ($serie) {
    $serie_esc = $conn->real_escape_string($serie);
    $result = $conn->query("SELECT e.*, c.nombre as cliente_nombre FROM equipos e JOIN clientes c ON e.cliente_id = c.id WHERE e.serie LIKE '%$serie_esc%' ORDER BY e.fecha_ingreso DESC");
    while ($eq = $result->fetch_assoc()) {
        $equipos[] = $eq;
    }
    
    if (count($equipos) > 0) {
        $equipo_ids = array_column($equipos, 'id');
        $ids_str = implode(',', $equipo_ids);
        $ordenes_result = $conn->query("SELECT o.*, e.marca, e.modelo, e.serie, c.nombre as cliente_nombre, u.nombre as tecnico_nombre FROM ordenes_servicio o LEFT JOIN equipos e ON o.equipo_id = e.id LEFT JOIN clientes c ON o.cliente_id = c.id LEFT JOIN usuarios u ON o.tecnico_id = u.id WHERE o.equipo_id IN ($ids_str) ORDER BY o.fecha_ingreso DESC");
        while ($ord = $ordenes_result->fetch_assoc()) {
            $ordenes[] = $ord;
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-journal-bookmark"></i> Historial de Equipo</h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Buscar por Número de Serie</label>
                    <div class="input-group">
                        <input type="text" name="serie" class="form-control" placeholder="Ingrese el número de serie del equipo" value="<?= htmlspecialchars($serie) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($serie): ?>
        <?php if (count($equipos) > 0): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        Se encontraron <strong><?= count($equipos) ?></strong> equipo(s) con serie: <strong><?= htmlspecialchars($serie) ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-laptop"></i> Equipos Encontrados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Serie</th>
                                    <th>Tipo</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipos as $eq): ?>
                                <tr>
                                    <td><?= htmlspecialchars($eq['cliente_nombre']) ?></td>
                                    <td><?= htmlspecialchars($eq['marca']) ?></td>
                                    <td><?= htmlspecialchars($eq['modelo'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($eq['serie'] ?? '-') ?></td>
                                    <td><?= ucfirst($eq['tipo_equipo']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($eq['fecha_ingreso'])) ?></td>
                                    <td>
                                        <a href="ver.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if (count($ordenes) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-ticket-detailed"></i> Órdenes de Servicio (<?= count($ordenes) ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Cliente</th>
                                    <th>Equipo</th>
                                    <th>Técnico</th>
                                    <th>Estado</th>
                                    <th>Costo</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Fecha Reparación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordenes as $ord): ?>
                                <tr>
                                    <td><strong><?= $ord['codigo'] ?></strong></td>
                                    <td><?= htmlspecialchars($ord['cliente_nombre']) ?></td>
                                    <td><?= htmlspecialchars($ord['marca'] . ' ' . $ord['modelo']) ?></td>
                                    <td><?= htmlspecialchars($ord['tecnico_nombre'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?= COLORES_ESTADO[$ord['estado']] ?? '#6c757d' ?>">
                                            <?= ESTADOS_ORDEN[$ord['estado']] ?? $ord['estado'] ?>
                                        </span>
                                    </td>
                                    <td><?= formatMoney($ord['costo_total']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($ord['fecha_ingreso'])) ?></td>
                                    <td><?= $ord['fecha_reparacion'] ? date('d/m/Y', strtotime($ord['fecha_reparacion'])) : '-' ?></td>
                                    <td>
                                        <a href="../ordenes/ver.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-primary">
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
            <?php else: ?>
            <div class="alert alert-warning">No hay órdenes de servicio para estos equipos</div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> No se encontraron equipos con la serie: <strong><?= htmlspecialchars($serie) ?></strong>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once '../include/footer.php'; ?>