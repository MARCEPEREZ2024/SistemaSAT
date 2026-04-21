<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Ver Cotización';
$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

$cotizacion = $conn->query("SELECT c.*, cl.nombre as cliente_nombre, cl.telefono, cl.dni, cl.direccion, e.marca, e.modelo FROM cotizaciones c JOIN clientes cl ON c.cliente_id = cl.id LEFT JOIN equipos e ON c.equipo_id = e.id WHERE c.id = $id")->fetch_assoc();

if (!$cotizacion) {
    redirect('cotizaciones/listar.php');
}

$detalle = $conn->query("SELECT * FROM detalle_cotizacion WHERE cotizacion_id = $id");

$total = 0;
$detalle_array = [];
while ($d = $detalle->fetch_assoc()) {
    $detalle_array[] = $d;
    $total += $d['importe'];
}

$success = '';
if ($action === 'aprobar' && $cotizacion['estado'] === 'pendiente') {
    $conn->query("UPDATE cotizaciones SET estado = 'aprobado' WHERE id = $id");
    $cotizacion['estado'] = 'aprobado';
    $success = 'Cotización aprobada';
}

if ($action === 'rechazar' && $cotizacion['estado'] === 'pendiente') {
    $conn->query("UPDATE cotizaciones SET estado = 'rechazado' WHERE id = $id");
    $cotizacion['estado'] = 'rechazado';
    $success = 'Cotización rechazada';
}

if ($action === 'convertir' && $cotizacion['estado'] === 'aprobado') {
    $numero = generateNumeroPresupuesto();
    $igv = $total * (IGV_PORCENTAJE / 100);
    $total_con_igv = $total + $igv;
    
    $stmt = $conn->prepare("INSERT INTO presupuestos (numero_presupuesto, cliente_id, descripcion, subtotal, igv, total, validez_dias, estado) VALUES (?, ?, ?, ?, ?, ?, 15, 'aprobado')");
    $stmt->bind_param("sisddd", $numero, $cotizacion['cliente_id'], $cotizacion['descripcion'], $total, $igv, $total_con_igv);
    
    if ($stmt->execute()) {
        $presupuesto_id = $conn->insert_id;
        foreach ($detalle_array as $d) {
            $stmt2 = $conn->prepare("INSERT INTO detalle_presupuesto (presupuesto_id, descripcion, cantidad, precio_unitario, importe) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("isidd", $presupuesto_id, $d['descripcion'], $d['cantidad'], $d['precio_unitario'], $d['importe']);
            $stmt2->execute();
        }
        $conn->query("UPDATE cotizaciones SET estado = 'convertido' WHERE id = $id");
        redirect('../presupuestos/ver.php?id=' . $presupuesto_id);
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-ruled"></i> Cotización: <?= $cotizacion['numero_cotizacion'] ?></h1>
        <a href="listar.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= getConfig('empresa_nombre') ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($cotizacion['fecha_creacion'])) ?></p>
                            <p><strong>Validez:</strong> <?= $cotizacion['validez_dias'] ?> días</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-<?= match($cotizacion['estado']) { 'pendiente'=>'warning','aprobado'=>'success','rechazado'=>'danger','convertido'=>'info' } ?> fs-6">
                                <?= ucfirst($cotizacion['estado']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Cliente</h6>
                            <p><?= htmlspecialchars($cotizacion['cliente_nombre']) ?></p>
                            <p>DNI: <?= $cotizacion['dni'] ?? '-' ?> | Tel: <?= $cotizacion['telefono'] ?? '-' ?></p>
                        </div>
                        <?php if ($cotizacion['marca']): ?>
                        <div class="col-md-6">
                            <h6>Equipo</h6>
                            <p><?= htmlspecialchars($cotizacion['marca'] . ' ' . $cotizacion['modelo']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($cotizacion['descripcion']): ?>
                    <div class="mb-4">
                        <h6>Descripción del Trabajo</h6>
                        <p><?= nl2br(htmlspecialchars($cotizacion['descripcion'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <table class="table table-bordered">
                        <thead>
                            <tr><th>Descripción</th><th class="text-center">Cant.</th><th class="text-end">P. Unit.</th><th class="text-end">Importe</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalle_array as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['descripcion']) ?></td>
                                <td class="text-center"><?= $d['cantidad'] ?></td>
                                <td class="text-end">S/ <?= number_format($d['precio_unitario'], 2) ?></td>
                                <td class="text-end">S/ <?= number_format($d['importe'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong>S/ <?= number_format($total, 2) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <?php if ($cotizacion['observaciones']): ?>
                    <p><strong>Observaciones:</strong> <?= htmlspecialchars($cotizacion['observaciones']) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($cotizacion['estado'] === 'pendiente'): ?>
                    <div class="mt-4">
                        <a href="?id=<?= $id ?>&action=aprobar" class="btn btn-success" onclick="return confirm('¿Aprobar?')">
                            <i class="bi bi-check"></i> Aprobar
                        </a>
                        <a href="?id=<?= $id ?>&action=rechazar" class="btn btn-danger" onclick="return confirm('¿Rechazar?')">
                            <i class="bi bi-x"></i> Rechazar
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($cotizacion['estado'] === 'aprobado'): ?>
                    <div class="mt-4">
                        <a href="?id=<?= $id ?>&action=convertir" class="btn btn-primary" onclick="return confirm('¿Convertir a Presupuesto?')">
                            <i class="bi bi-arrow-right"></i> Convertir a Presupuesto
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>