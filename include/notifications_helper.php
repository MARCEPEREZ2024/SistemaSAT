<?php

function get_notificaciones($usuario_id, $limit = 10) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT n.*, o.codigo as orden_codigo
        FROM notificaciones n
        LEFT JOIN ordenes_servicio o ON n.orden_id = o.id
        WHERE n.usuario_id = ? OR n.usuario_id IS NULL
        ORDER BY n.fecha DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $usuario_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function get_notificaciones_no_leidas($usuario_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM notificaciones 
        WHERE usuario_id = ? AND leida = 0
    ");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}

function crear_notificacion($titulo, $mensaje, $tipo, $usuario_id = null, $orden_id = null, $link = '') {
    $conn = getConnection();
    $stmt = $conn->prepare("
        INSERT INTO notificaciones (titulo, mensaje, tipo, usuario_id, orden_id, link, fecha) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssiss", $titulo, $mensaje, $tipo, $usuario_id, $orden_id, $link);
    return $stmt->execute();
}

function marcar_notificacion_leida($id, $usuario_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    return $stmt->execute();
}

function marcar_todas_leidas($usuario_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0");
    $stmt->bind_param("i", $usuario_id);
    return $stmt->execute();
}

function eliminar_notificacion($id, $usuario_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    return $stmt->execute();
}

function get_icon_notificacion($tipo) {
    $icons = [
        'orden' => 'bi-ticket-detailed',
        'cliente' => 'bi-person',
        'factura' => 'bi-receipt',
        'inventario' => 'bi-box-seam',
        'sistema' => 'bi-gear',
        'alerta' => 'bi-exclamation-triangle',
        'success' => 'bi-check-circle',
        'info' => 'bi-info-circle'
    ];
    return $icons[$tipo] ?? 'bi-bell';
}

function get_color_notificacion($tipo) {
    $colors = [
        'orden' => 'primary',
        'cliente' => 'info',
        'factura' => 'success',
        'inventario' => 'warning',
        'sistema' => 'secondary',
        'alerta' => 'danger',
        'success' => 'success',
        'info' => 'info'
    ];
    return $colors[$tipo] ?? 'primary';
}