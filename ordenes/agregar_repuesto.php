<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$orden_id = (int)$_POST['orden_id'];
$repuesto_id = (int)$_POST['repuesto_id'];
$cantidad = (int)$_POST['cantidad'];

$repuesto = getRepuestoById($repuesto_id);
if (!$repuesto) {
redirect("ordenes/ver.php?id=$orden_id&msg=Repuesto no encontrado");
    }
    if ($repuesto['stock'] < $cantidad) {
        redirect("ordenes/ver.php?id=$orden_id&msg=Stock insuficiente");
    }

$stmt = $conn->prepare("INSERT INTO repuestos_orden (orden_id, repuesto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiid", $orden_id, $repuesto_id, $cantidad, $repuesto['precio_venta']);
$stmt->execute();

$stmt = $conn->prepare("UPDATE repuestos SET stock = stock - ? WHERE id = ?");
$stmt->bind_param("ii", $cantidad, $repuesto_id);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO movimientos_inventario (repuesto_id, tipo, cantidad, orden_id, usuario_id, fecha) VALUES (?, 'salida', ?, ?, ?, NOW())");
$stmt->bind_param("iiii", $repuesto_id, $cantidad, $orden_id, $_SESSION['usuario_id']);
$stmt->execute();

$orden = getOrdenById($orden_id);
$stmt = $conn->prepare("UPDATE ordenes_servicio SET costo_total = costo_diagnostico + costo_reparacion + (SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM repuestos_orden WHERE orden_id = ?) WHERE id = ?");
$stmt->bind_param("ii", $orden_id, $orden_id);
$stmt->execute();

redirect("ordenes/ver.php?id=$orden_id&msg=Repuesto agregado correctamente");
?>