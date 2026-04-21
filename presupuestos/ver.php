<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Ver Presupuesto';
$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

$presupuesto = $conn->query("SELECT p.*, c.nombre as cliente_nombre, c.telefono, c.dni, c.direccion, c.email, o.codigo as orden_codigo, e.marca, e.modelo FROM presupuestos p LEFT JOIN clientes c ON p.cliente_id = c.id LEFT JOIN ordenes_servicio o ON p.orden_id = o.id LEFT JOIN equipos e ON o.equipo_id = e.id WHERE p.id = $id")->fetch_assoc();

if (!$presupuesto) {
    redirect('presupuestos/listar.php');
}

$detalle = $conn->query("SELECT * FROM detalle_presupuesto WHERE presupuesto_id = $id");

$success = '';
$error = '';

if ($action === 'aprobar' && $presupuesto['estado'] === 'pendiente') {
    $conn->query("UPDATE presupuestos SET estado = 'aprobado' WHERE id = $id");
    $presupuesto['estado'] = 'aprobado';
    $success = 'Presupuesto aprobado correctamente';
}

if ($action === 'rechazar' && $presupuesto['estado'] === 'pendiente') {
    $conn->query("UPDATE presupuestos SET estado = 'rechazado' WHERE id = $id");
    $presupuesto['estado'] = 'rechazado';
    $success = 'Presupuesto rechazado';
}

if ($action === 'convertir' && $presupuesto['estado'] === 'aprobado') {
    $check = $conn->query("SELECT id FROM facturas WHERE orden_id = " . ($presupuesto['orden_id'] ?? 0))->num_rows;
    if ($check > 0) {
        $error = 'Ya existe una factura para esta orden';
    } else {
        $numero_factura = generateNumeroFactura();
        $stmt = $conn->prepare("INSERT INTO facturas (numero_factura, orden_id, cliente_id, subtotal, igv, total, tipo_pago, observaciones, fecha_emision) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $tipo_pago = 'credito';
        $obs = 'Presupuesto: ' . $presupuesto['numero_presupuesto'];
        $stmt->bind_param("siidddss", $numero_factura, $presupuesto['orden_id'], $presupuesto['cliente_id'], $presupuesto['subtotal'], $presupuesto['igv'], $presupuesto['total'], $tipo_pago, $obs);
        
        if ($stmt->execute()) {
            $factura_id = $conn->insert_id;
            
            while ($d = $detalle->fetch_assoc()) {
                $stmt2 = $conn->prepare("INSERT INTO detalle_factura (factura_id, descripcion, cantidad, precio_unitario, importe) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("isidd", $factura_id, $d['descripcion'], $d['cantidad'], $d['precio_unitario'], $d['importe']);
                $stmt2->execute();
            }
            
            $conn->query("UPDATE presupuestos SET estado = 'convertido' WHERE id = $id");
            redirect('../SistemaSAT/facturacion/ver.php?id=' . $factura_id);
        } else {
            $error = 'Error al convertir a factura';
        }
    }
}

$detalle = $conn->query("SELECT * FROM detalle_presupuesto WHERE presupuesto_id = $id");

$empresa_nombre = getConfig('empresa_nombre');
$empresa_direccion = getConfig('empresa_direccion');
$empresa_telefono = getConfig('empresa_telefono');
$empresa_ruc = getConfig('empresa_ruc');
$moneda = getConfig('moneda') ?: 'S/';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-text"></i> Presupuesto: <?= $presupuesto['numero_presupuesto'] ?></h1>
        <div>
            <a href="imprimir.php?id=<?= $id ?>" class="btn btn-secondary" target="_blank">
                <i class="bi bi-printer"></i> Imprimir
            </a>
            <a href="listar.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
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
                            <h4>PRESUPUESTO</h4>
                            <p class="mb-0"><strong>N°:</strong> <?= $presupuesto['numero_presupuesto'] ?></p>
                            <p class="mb-0"><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($presupuesto['fecha_creacion'])) ?></p>
                            <p class="mb-0"><strong>Validez:</strong> <?= $presupuesto['validez_dias'] ?> días</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Cliente:</p>
                            <h6><?= htmlspecialchars($presupuesto['cliente_nombre']) ?></h6>
                            <p class="mb-0"><?= $presupuesto['telefono'] ?></p>
                            <p class="mb-0">DNI: <?= $presupuesto['dni'] ?? '-' ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if ($presupuesto['orden_id']): ?>
                            <p class="text-muted mb-1">Orden de Servicio:</p>
                            <h6><?= $presupuesto['orden_codigo'] ?></h6>
                            <p class="mb-0"><?= htmlspecialchars($presupuesto['marca'] . ' ' . $presupuesto['modelo']) ?></p>
                            <?php endif; ?>
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
                                <?php while ($d = $detalle->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['descripcion']) ?></td>
                                    <td class="text-center"><?= $d['cantidad'] ?></td>
                                    <td class="text-end"><?= $moneda . ' ' . number_format($d['precio_unitario'], 2) ?></td>
                                    <td class="text-end"><?= $moneda . ' ' . number_format($d['importe'], 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><?= $moneda . ' ' . number_format($presupuesto['subtotal'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">IGV (<?= IGV_PORCENTAJE ?>%):</td>
                                    <td class="text-end"><?= $moneda . ' ' . number_format($presupuesto['igv'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                    <td class="text-end"><strong><?= $moneda . ' ' . number_format($presupuesto['total'], 2) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if ($presupuesto['observaciones']): ?>
                    <div class="mt-3">
                        <p class="text-muted mb-1">Observaciones:</p>
                        <p><?= htmlspecialchars($presupuesto['observaciones']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> 
                                <?php 
                                $badgeClass = match($presupuesto['estado']) {
                                    'pendiente' => 'warning',
                                    'aprobado' => 'success',
                                    'rechazado' => 'danger',
                                    'convertido' => 'info',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($presupuesto['estado']) ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($presupuesto['estado'] === 'pendiente'): ?>
                    <div class="mt-4">
                        <a href="?id=<?= $id ?>&action=aprobar" class="btn btn-success" onclick="return confirm('¿Aprobar este presupuesto?')">
                            <i class="bi bi-check-circle"></i> Aprobar
                        </a>
                        <a href="?id=<?= $id ?>&action=rechazar" class="btn btn-danger" onclick="return confirm('¿Rechazar este presupuesto?')">
                            <i class="bi bi-x-circle"></i> Rechazar
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($presupuesto['estado'] === 'aprobado'): ?>
                    <div class="mt-4">
                        <a href="?id=<?= $id ?>&action=convertir" class="btn btn-primary" onclick="return confirm('¿Convertir este presupuesto a factura?')">
                            <i class="bi bi-receipt"></i> Convertir a Factura
                        </a>
                    </div>
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
                            <i class="bi bi-printer"></i> Imprimir Presupuesto
                        </a>
                        <?php if ($presupuesto['orden_id']): ?>
                        <a href="../ordenes/ver.php?id=<?= $presupuesto['orden_id'] ?>" class="btn btn-outline-primary">
                            <i class="bi bi-ticket-detailed"></i> Ver Orden
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>