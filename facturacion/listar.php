<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Facturación';
$estado = $_GET['estado'] ?? '';
$facturas = getAllFacturas($estado);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-receipt"></i> Facturación</h1>
        <div>
            <a href="crear.php?directa=1" class="btn btn-warning">
                <i class="bi bi-receipt-cutoff"></i> Factura Directa
            </a>
            <a href="crear.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nueva Factura
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="pagado" <?= $estado === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                            <option value="cancelado" <?= $estado === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Tipo Pago</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($factura = $facturas->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $factura['numero_factura'] ?></strong></td>
                            <td><?= $factura['orden_codigo'] ?></td>
                            <td><?= htmlspecialchars($factura['cliente_nombre']) ?></td>
                            <td><?= formatMoney($factura['total']) ?></td>
                            <td><?= ucfirst($factura['tipo_pago']) ?></td>
                            <td>
                                <span class="badge bg-<?= $factura['estado_pago'] === 'pagado' ? 'success' : ($factura['estado_pago'] === 'pendiente' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($factura['estado_pago']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($factura['fecha_emision'])) ?></td>
                            <td>
                                <a href="ver.php?id=<?= $factura['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="imprimir.php?id=<?= $factura['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Imprimir">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($facturas->num_rows == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No se encontraron facturas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>