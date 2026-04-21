<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';

$id = $_GET['id'] ?? 0;

$presupuesto = $conn->query("SELECT p.*, c.nombre as cliente_nombre, c.telefono, c.dni, c.direccion, c.email, o.codigo as orden_codigo, e.marca, e.modelo FROM presupuestos p LEFT JOIN clientes c ON p.cliente_id = c.id LEFT JOIN ordenes_servicio o ON p.orden_id = o.id LEFT JOIN equipos e ON o.equipo_id = e.id WHERE p.id = $id")->fetch_assoc();

if (!$presupuesto) {
    die('Presupuesto no encontrado');
}

$detalle = $conn->query("SELECT * FROM detalle_presupuesto WHERE presupuesto_id = $id");

$empresa_nombre = getConfig('empresa_nombre');
$empresa_direccion = getConfig('empresa_direccion');
$empresa_telefono = getConfig('empresa_telefono');
$empresa_ruc = getConfig('empresa_ruc');
$moneda = getConfig('moneda') ?: 'S/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuesto <?= $presupuesto['numero_presupuesto'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .empresa { font-size: 14px; font-weight: bold; }
        .documento { text-align: right; }
        .cliente { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totales { margin-left: auto; width: 300px; }
        .footer { margin-top: 30px; font-size: 10px; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="empresa">
            <h2><?= htmlspecialchars($empresa_nombre) ?></h2>
            <p>RUC: <?= htmlspecialchars($empresa_ruc) ?></p>
            <p><?= htmlspecialchars($empresa_direccion) ?></p>
            <p>Tel: <?= htmlspecialchars($empresa_telefono) ?></p>
        </div>
        <div class="documento">
            <h1>PRESUPUESTO</h1>
            <p><strong>N°:</strong> <?= $presupuesto['numero_presupuesto'] ?></p>
            <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($presupuesto['fecha_creacion'])) ?></p>
            <p><strong>Validez:</strong> <?= $presupuesto['validez_dias'] ?> días</p>
        </div>
    </div>
    
    <div class="cliente">
        <h3>Cliente</h3>
        <p><strong>Nombre:</strong> <?= htmlspecialchars($presupuesto['cliente_nombre']) ?></p>
        <p><strong>DNI:</strong> <?= $presupuesto['dni'] ?? '-' ?> | <strong>Teléfono:</strong> <?= $presupuesto['telefono'] ?? '-' ?></p>
        <?php if ($presupuesto['orden_id']): ?>
        <p><strong>Orden:</strong> <?= $presupuesto['orden_codigo'] ?> - <?= htmlspecialchars($presupuesto['marca'] . ' ' . $presupuesto['modelo']) ?></p>
        <?php endif; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">P. Unit.</th>
                <th class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($d = $detalle->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($d['descripcion']) ?></td>
                <td class="text-center"><?= $d['cantidad'] ?></td>
                <td class="text-right"><?= $moneda . ' ' . number_format($d['precio_unitario'], 2) ?></td>
                <td class="text-right"><?= $moneda . ' ' . number_format($d['importe'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <table class="totales">
        <tr>
            <td class="text-right"><strong>Subtotal:</strong></td>
            <td class="text-right"><?= $moneda . ' ' . number_format($presupuesto['subtotal'], 2) ?></td>
        </tr>
        <tr>
            <td class="text-right">IGV (<?= IGV_PORCENTAJE ?>%):</td>
            <td class="text-right"><?= $moneda . ' ' . number_format($presupuesto['igv'], 2) ?></td>
        </tr>
        <tr>
            <td class="text-right"><strong>TOTAL:</strong></td>
            <td class="text-right"><strong><?= $moneda . ' ' . number_format($presupuesto['total'], 2) ?></strong></td>
        </tr>
    </table>
    
    <?php if ($presupuesto['observaciones']): ?>
    <p><strong>Observaciones:</strong> <?= htmlspecialchars($presupuesto['observaciones']) ?></p>
    <?php endif; ?>
    
    <div class="footer">
        <p>Este presupuesto tiene validez de <?= $presupuesto['validez_dias'] ?> días.</p>
        <p>Para aprobar este presupuesto, contactenos al teléfono <?= htmlspecialchars($empresa_telefono) ?></p>
    </div>
    
    <script>window.print();</script>
</body>
</html>