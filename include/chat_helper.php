<?php

function enviarMensajeInterno($conn, $remitente_id, $destinatario_id, $mensaje) {
    $stmt = $conn->prepare("INSERT INTO mensajes_internos (remitente_id, destinatario_id, mensaje) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $remitente_id, $destinatario_id, $mensaje);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    
    if ($id) {
        $remitente = $conn->query("SELECT nombre FROM usuarios WHERE id = $remitente_id")->fetch_assoc();
        $remitente_nombre = $remitente['nombre'] ?? 'Usuario';
        $mensaje_corto = mb_substr($mensaje, 0, 50);
        
        $link = $destinatario_id 
            ? BASE_URL . 'chat/index.php?tipo=privado&usuario=' . $remitente_id 
            : BASE_URL . 'chat/index.php?tipo=global';
        
        try {
            if ($destinatario_id) {
                $notif = $conn->prepare("INSERT INTO notificaciones (titulo, mensaje, tipo, usuario_id, link, fecha) VALUES (?, ?, 'chat', ?, ?, NOW())");
                $titulo = 'Nuevo mensaje de ' . $remitente_nombre;
                $notif->bind_param("sss", $titulo, $mensaje_corto, $destinatario_id, $link);
                $notif->execute();
                $notif->close();
            } else {
                $usuarios = $conn->query("SELECT id FROM usuarios WHERE id != $remitente_id");
                while ($u = $usuarios->fetch_assoc()) {
                    $notif = $conn->prepare("INSERT INTO notificaciones (titulo, mensaje, tipo, usuario_id, link, fecha) VALUES (?, ?, 'chat', ?, ?, NOW())");
                    $titulo = 'Nuevo mensaje global de ' . $remitente_nombre;
                    $notif->bind_param("sss", $titulo, $mensaje_corto, $u['id'], $link);
                    $notif->execute();
                    $notif->close();
                }
            }
        } catch (Exception $e) {
            error_log('Error creando notificacion: ' . $e->getMessage());
        }
    }
    
    return $id;
}

function getMensajesGlobales($conn, $limit = 50) {
    $result = $conn->query("
        SELECT m.*, u.nombre as remitente_nombre, u.rol as remitente_rol
        FROM mensajes_internos m
        JOIN usuarios u ON m.remitente_id = u.id
        WHERE m.destinatario_id IS NULL
        ORDER BY m.created_at DESC
        LIMIT $limit
    ");
    return $result;
}

function getMensajesPrivados($conn, $user_id, $otro_id, $limit = 50) {
    $stmt = $conn->prepare("
        SELECT m.*, u.nombre as remitente_nombre, u.rol as remitente_rol
        FROM mensajes_internos m
        JOIN usuarios u ON m.remitente_id = u.id
        WHERE (m.remitente_id = ? AND m.destinatario_id = ?)
           OR (m.remitente_id = ? AND m.destinatario_id = ?)
        ORDER BY m.created_at DESC
        LIMIT $limit
    ");
    $stmt->bind_param("iiii", $user_id, $otro_id, $otro_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

function getConversaciones($conn, $user_id) {
    $result = $conn->query("
        SELECT DISTINCT 
            CASE 
                WHEN m.remitente_id = $user_id THEN m.destinatario_id 
                ELSE m.remitente_id 
            END as otro_id,
            u.nombre as otro_nombre,
            u.rol as otro_rol,
            (SELECT mensaje FROM mensajes_internos 
             WHERE (remitente_id = $user_id AND destinatario_id = otro_id) 
                OR (remitente_id = otro_id AND destinatario_id = $user_id)
             ORDER BY created_at DESC LIMIT 1) as ultimo_mensaje,
            (SELECT created_at FROM mensajes_internos 
             WHERE (remitente_id = $user_id AND destinatario_id = otro_id) 
                OR (remitente_id = otro_id AND destinatario_id = $user_id)
             ORDER BY created_at DESC LIMIT 1) as fecha_ultimo,
            (SELECT COUNT(*) FROM mensajes_internos 
             WHERE destinatario_id = $user_id AND remitente_id = otro_id AND leido = 0) as sin_leer
        FROM mensajes_internos m
        LEFT JOIN usuarios u ON u.id = CASE 
            WHEN m.remitente_id = $user_id THEN m.destinatario_id 
            ELSE m.remitente_id 
        END
        WHERE (m.remitente_id = $user_id OR m.destinatario_id = $user_id)
          AND m.destinatario_id IS NOT NULL
        ORDER BY fecha_ultimo DESC
    ");
    return $result;
}

function getMensajesNoLeidos($conn, $user_id) {
    $result = $conn->query("SELECT COUNT(*) as total FROM mensajes_internos WHERE destinatario_id = $user_id AND leido = 0");
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function marcarMensajeLeido($conn, $mensaje_id, $user_id) {
    $stmt = $conn->prepare("UPDATE mensajes_internos SET leido = 1 WHERE id = ? AND destinatario_id = ?");
    $stmt->bind_param("ii", $mensaje_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

function getUsuariosParaChat($conn, $exclude_id = null) {
    $sql = "SELECT id, nombre, rol FROM usuarios WHERE estado = 'activo'";
    if ($exclude_id) {
        $sql .= " AND id != $exclude_id";
    }
    $sql .= " ORDER BY rol, nombre";
    return $conn->query($sql);
}