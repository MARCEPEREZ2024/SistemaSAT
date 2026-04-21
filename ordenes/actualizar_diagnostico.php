<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$orden_id = (int)$_POST['orden_id'];
$diagnostico = sanitize($_POST['diagnostico']);
$solucion = sanitize($_POST['solucion']);
$costo_reparacion = (float)$_POST['costo_reparacion'];

$stmt = $conn->prepare("UPDATE ordenes_servicio SET diagnostico = ?, solucion = ?, costo_reparacion = ?, costo_total = costo_diagnostico + ? WHERE id = ?");
$stmt->bind_param("ssidi", $diagnostico, $solucion, $costo_reparacion, $costo_reparacion, $orden_id);

if ($stmt->execute()) {
    redirect("ordenes/ver.php?id=$orden_id&msg=Diagnóstico actualizado correctamente");
} else {
    redirect("ordenes/ver.php?id=$orden_id&msg=Error al actualizar");
}
?>