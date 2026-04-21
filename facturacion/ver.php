<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Ver Factura';
$id = $_GET['id'] ?? 0;

$factura = getFacturaById($id);
if (!$factura) {
    redirect('listar.php');
}

$detalle = getDetalleFactura($id);
$detalle_array = [];
while($d = $detalle->fetch_assoc()) {
    $detalle_array[] = $d;
}
$empresa_nombre = getConfig('empresa_nombre');
$empresa_direccion = getConfig('empresa_direccion');
$empresa_telefono = getConfig('empresa_telefono');
$empresa_ruc = getConfig('empresa_ruc');
$moneda = getConfig('moneda') ?: 'S/';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_pagado'])) {
    $stmt = $conn->prepare("UPDATE facturas SET estado_pago = 'pagado', fecha_pago = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $factura = getFacturaById($id);
        $success = 'Pago registrado correctamente';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_factura'])) {
    $stmt = $conn->prepare("UPDATE facturas SET estado_pago = 'cancelado' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $factura = getFacturaById($id);
        $success = 'Factura cancelada';
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-receipt"></i> Factura: <?= $factura['numero_factura'] ?></h1>
        <div>
            <a href="imprimir.php?id=<?= $id ?>" class="btn btn-secondary" target="_blank">
                <i class="bi bi-printer"></i> Imprimir
            </a>
            <a href="listar.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-shop"></i> <?= htmlspecialchars($empresa_nombre) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>RUC:</strong> <?= htmlspecialchars($empresa_ruc) ?></p>
                            <p class="mb-1"><strong>Dirección:</strong> <?= htmlspecialchars($empresa_direccion) ?></p>
                            <p class="mb-0"><strong>Teléfono:</strong> <?= htmlspecialchars($empresa_telefono) ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h4>FACTURA ELECTRÓNICA</h4>
                            <p class="mb-0"><strong>N°:</strong> <?= $factura['numero_factura'] ?></p>
                            <p class="mb-0"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($factura['fecha_emision'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Cliente:</p>
                            <h6><?= htmlspecialchars($factura['cliente_nombre']) ?></h6>
                            <p class="mb-0"><?= htmlspecialchars($factura['telefono']) ?></p>
                            <p class="mb-0"><?= htmlspecialchars($factura['direccion'] ?? '') ?></p>
                            <p class="mb-0">DNI: <?= htmlspecialchars($factura['dni'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="text-muted mb-1">Orden de Servicio:</p>
                            <h6><?= $factura['orden_codigo'] ?></h6>
                            <p class="mb-0"><?= htmlspecialchars($factura['marca'] . ' ' . $factura['modelo']) ?></p>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Descripción</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">P. Unit.</th>
                                    <th class="text-end">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detalle_array as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['descripcion']) ?></td>
                                    <td class="text-center"><?= $d['cantidad'] ?></td>
                                    <td class="text-end"><?= $moneda . ' ' . number_format($d['precio_unitario'], 2) ?></td>
                                    <td class="text-end"><?= $moneda . ' ' . number_format($d['importe'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($detalle_array) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted">Sin detalle</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><?= $moneda . ' ' . number_format($factura['subtotal'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">IGV (<?= IGV_PORCENTAJE ?>%):</td>
                                    <td class="text-end"><?= $moneda . ' ' . number_format($factura['igv'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                    <td class="text-end"><strong><?= $moneda . ' ' . number_format($factura['total'], 2) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Tipo de Pago:</strong> <?= ucfirst($factura['tipo_pago']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> 
                                <span class="badge bg-<?= $factura['estado_pago'] === 'pagado' ? 'success' : ($factura['estado_pago'] === 'pendiente' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($factura['estado_pago']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($factura['observaciones']): ?>
                    <div class="mt-3">
                        <p class="text-muted mb-1">Observaciones:</p>
                        <p><?= htmlspecialchars($factura['observaciones']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($factura['estado_pago'] === 'pendiente'): ?>
                    <form method="POST" class="mt-4">
                        <button type="submit" name="marcar_pagado" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Marcar como Pagado
                        </button>
                        <button type="submit" name="cancelar_factura" class="btn btn-danger" onclick="return confirm('¿Está seguro de cancelar esta factura?')">
                            <i class="bi bi-x-circle"></i> Cancelar Factura
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="imprimir.php?id=<?= $id ?>" class="btn btn-primary" target="_blank">
                            <i class="bi bi-printer"></i> Imprimir Factura
                        </a>
                        <a href="../ordenes/ver.php?id=<?= $factura['orden_id'] ?>" class="btn btn-outline-primary">
                            <i class="bi bi-ticket-detailed"></i> Ver Orden
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>