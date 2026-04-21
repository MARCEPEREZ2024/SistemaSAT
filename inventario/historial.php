<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Historial de Inventario';
$id = $_GET['id'] ?? 0;

$repuesto = getRepuestoById($id);
if (!$repuesto) {
    redirect('inventario/listar.php');
}

$movimientos = getMovimientosRepuesto($id);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-clock-history"></i> Historial - <?= htmlspecialchars($repuesto['nombre']) ?></h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Datos del Repuesto</h5>
                </div>
                <div class="card-body">
                    <p><strong>Código:</strong> <?= htmlspecialchars($repuesto['codigo']) ?></p>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($repuesto['nombre']) ?></p>
                    <p><strong>Categoría:</strong> <?= htmlspecialchars($repuesto['categoria'] ?? '-') ?></p>
                    <p><strong>Stock Actual:</strong> 
                        <?php if ($repuesto['stock'] <= $repuesto['stock_minimo']): ?>
                        <span class="badge bg-danger"><?= $repuesto['stock'] ?></span>
                        <?php else: ?>
                        <span class="badge bg-success"><?= $repuesto['stock'] ?></span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Stock Mínimo:</strong> <?= $repuesto['stock_minimo'] ?></p>
                    <p><strong>Precio Venta:</strong> <?= formatMoney($repuesto['precio_venta']) ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Movimientos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Usuario</th>
                                    <th>Orden</th>
                                    <th>Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($mov = $movimientos->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $mov['tipo'] === 'entrada' ? 'success' : ($mov['tipo'] === 'salida' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><?= $mov['cantidad'] > 0 ? '+' : '' ?><?= $mov['cantidad'] ?></td>
                                    <td><?= htmlspecialchars($mov['usuario_nombre'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($mov['orden_codigo'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($mov['nota'] ?? '-') ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($movimientos->num_rows == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No hay movimientos</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>