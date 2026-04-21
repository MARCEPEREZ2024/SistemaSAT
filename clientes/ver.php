<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Ver Cliente';
$id = $_GET['id'] ?? 0;

$cliente = getClienteById($id);
if (!$cliente) {
    redirect('clientes/listar.php');
}

$equipos = getEquiposByCliente($id);
$ordenes = $conn->query("SELECT o.*, e.marca, e.modelo FROM ordenes_servicio o LEFT JOIN equipos e ON o.equipo_id = e.id WHERE o.cliente_id = $id ORDER BY o.fecha_ingreso DESC LIMIT 10");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-person"></i> <?= htmlspecialchars($cliente['nombre']) ?></h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos del Cliente</h5>
                </div>
                <div class="card-body">
                    <p><strong>Email:</strong> <?= htmlspecialchars($cliente['email'] ?? '-') ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono']) ?></p>
                    <p><strong>DNI:</strong> <?= htmlspecialchars($cliente['dni'] ?? '-') ?></p>
                    <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion'] ?? '-') ?></p>
                    <p><strong>Registrado:</strong> <?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?></p>
                    <a href="editar.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-laptop"></i> Equipos</h5>
                    <a href="../equipos/agregar.php?cliente_id=<?= $id ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Nuevo Equipo
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($equipos->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Tipo</th>
                                        <th>Serie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($eq = $equipos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($eq['marca']) ?></td>
                                        <td><?= htmlspecialchars($eq['modelo'] ?? '-') ?></td>
                                        <td><?= ucfirst($eq['tipo_equipo']) ?></td>
                                        <td><?= htmlspecialchars($eq['serie'] ?? '-') ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No hay equipos registrados</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-ticket-detailed"></i> Órdenes de Servicio</h5>
                    <a href="../ordenes/agregar.php?cliente_id=<?= $id ?>" class="btn btn-sm btn-primary">
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
                                        <th>Equipo</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ord = $ordenes->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= $ord['codigo'] ?></strong></td>
                                        <td><?= htmlspecialchars($ord['marca'] . ' ' . $ord['modelo']) ?></td>
                                        <td>
                                            <span class="badge" style="background-color: <?= COLORES_ESTADO[$ord['estado']] ?? '#6c757d' ?>">
                                                <?= ESTADOS_ORDEN[$ord['estado']] ?? $ord['estado'] ?>
                                            </span>
                                        </td>
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