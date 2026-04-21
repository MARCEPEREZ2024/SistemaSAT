<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$orden_id = (int)$_POST['orden_id'];
$observaciones = sanitize($_POST['observaciones']);

$stmt = $conn->prepare("UPDATE ordenes_servicio SET observaciones = ? WHERE id = ?");
$stmt->bind_param("si", $observaciones, $orden_id);

if ($stmt->execute()) {
redirect("ordenes/ver.php?id=$orden_id&msg=Observaciones actualizadas correctamente");
    } else {
        redirect("ordenes/ver.php?id=$orden_id&msg=Error al actualizar");
    }
?>