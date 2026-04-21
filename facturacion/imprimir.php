<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';

$id = $_GET['id'] ?? 0;

$factura = getFacturaById($id);
if (!$factura) {
    die('Factura no encontrada');
}

$detalle = getDetalleFactura($id);
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
    <title>Factura <?= $factura['numero_factura'] ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        .factura { max-width: 800px; margin: 0 auto; border: 1px solid #333; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .empresa { width: 50%; }
        .empresa h1 { font-size: 24px; margin-bottom: 5px; }
        .info-factura { width: 50%; text-align: right; }
        .info-factura h2 { font-size: 18px; margin-bottom: 5px; }
        .datos { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .cliente, .orden { width: 48%; }
        .cliente h3, .orden h3 { font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totales { margin-top: 20px; }
        .totales table { width: 300px; margin-left: auto; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
        @media print {
            body { padding: 0; }
            .factura { border: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="factura">
        <div class="header">
            <div class="empresa">
                <h1><?= htmlspecialchars($empresa_nombre) ?></h1>
                <p>RUC: <?= htmlspecialchars($empresa_ruc) ?></p>
                <p><?= htmlspecialchars($empresa_direccion) ?></p>
                <p>Teléfono: <?= htmlspecialchars($empresa_telefono) ?></p>
            </div>
            <div class="info-factura">
                <h2>FACTURA ELECTRÓNICA</h2>
                <p><strong>N°:</strong> <?= $factura['numero_factura'] ?></p>
                <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($factura['fecha_emision'])) ?></p>
            </div>
        </div>
        
        <div class="datos">
            <div class="cliente">
                <h3>Cliente</h3>
                <p><strong><?= htmlspecialchars($factura['cliente_nombre']) ?></strong></p>
                <p>DNI: <?= htmlspecialchars($factura['dni'] ?? '-') ?></p>
                <p>Teléfono: <?= htmlspecialchars($factura['telefono']) ?></p>
                <p><?= htmlspecialchars($factura['direccion'] ?? '') ?></p>
            </div>
            <div class="orden">
                <h3>Orden de Servicio</h3>
                <p><strong>Código:</strong> <?= $factura['orden_codigo'] ?></p>
                <p><strong>Equipo:</strong> <?= htmlspecialchars($factura['marca'] . ' ' . $factura['modelo']) ?></p>
                <p><strong>Tipo Pago:</strong> <?= ucfirst($factura['tipo_pago']) ?></p>
            </div>
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
        
        <div class="totales">
            <table>
                <tr>
                    <td class="text-right"><strong>Subtotal:</strong></td>
                    <td class="text-right"><?= $moneda . ' ' . number_format($factura['subtotal'], 2) ?></td>
                </tr>
                <tr>
                    <td class="text-right">IGV (<?= IGV_PORCENTAJE ?>%):</td>
                    <td class="text-right"><?= $moneda . ' ' . number_format($factura['igv'], 2) ?></td>
                </tr>
                <tr>
                    <td class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong><?= $moneda . ' ' . number_format($factura['total'], 2) ?></strong></td>
                </tr>
            </table>
        </div>
        
        <?php if ($factura['observaciones']): ?>
        <div style="margin-top: 20px;">
            <p><strong>Observaciones:</strong> <?= htmlspecialchars($factura['observaciones']) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Gracias por su preferencia</p>
            <p>Este documento es una representación impresa de la factura electrónica</p>
        </div>
    </div>
</body>
</html>