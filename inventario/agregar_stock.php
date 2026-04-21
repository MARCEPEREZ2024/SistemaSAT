<?php
require_once '../config/database.php';
require_once '../config/config.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$repuesto_id = (int)$_POST['repuesto_id'];
$cantidad = (int)$_POST['cantidad'];
$nota = sanitize($_POST['nota']);

if ($cantidad <= 0) {
    redirect('../inventario/listar.php');
}

$stmt = $conn->prepare("UPDATE repuestos SET stock = stock + ? WHERE id = ?");
$stmt->bind_param("ii", $cantidad, $repuesto_id);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO movimientos_inventario (repuesto_id, tipo, cantidad, usuario_id, nota, fecha) VALUES (?, 'entrada', ?, ?, ?, NOW())");
$stmt->bind_param("iiis", $repuesto_id, $cantidad, $_SESSION['usuario_id'], $nota);
$stmt->execute();

redirect('../inventario/listar.php?msg=Stock agregado correctamente');
?>