<?php

function enviarMensajeInterno($conn, $remitente_id, $destinatario_id, $mensaje) {
    $stmt = $conn->prepare("INSERT INTO mensajes_internos (remitente_id, destinatario_id, mensaje) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $remitente_id, $destinatario_id, $mensaje);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    
    if ($id) {
        $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $remitente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $remitente = $result->fetch_assoc();
        $stmt->close();
        
        $remitente_nombre = $remitente['nombre'] ?? 'Usuario';
        $mensaje_corto = mb_substr($mensaje, 0, 50);
        
        $link = $destinatario_id 
            ? BASE_URL . 'chat/index.php?tipo=privado&usuario=' . $remitente_id 
            : BASE_URL . 'chat/index.php?tipo=global';
        
        try {
            if ($destinatario_id) {
                try {
                    $notif = $conn->prepare("INSERT INTO notificaciones (titulo, mensaje, tipo, usuario_id, link, fecha) VALUES (?, ?, 'chat', ?, ?, NOW())");
                    $titulo = 'Nuevo mensaje de ' . $remitente_nombre;
                    $notif->bind_param("sssi", $titulo, $mensaje_corto, $destinatario_id, $link);
                    $notif->execute();
                    $notif->close();
                } catch (Exception $e) {}
            } else {
                try {
                    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id != ?");
                    $stmt->bind_param("i", $remitente_id);
                    $stmt->execute();
                    $usuarios = $stmt->get_result();
                    
                    while ($u = $usuarios->fetch_assoc()) {
                        $notif = $conn->prepare("INSERT INTO notificaciones (titulo, mensaje, tipo, usuario_id, link, fecha) VALUES (?, ?, 'chat', ?, ?, NOW())");
                        $titulo = 'Nuevo mensaje global de ' . $remitente_nombre;
                        $notif->bind_param("sssi", $titulo, $mensaje_corto, $u['id'], $link);
                        $notif->execute();
                        $notif->close();
                    }
                    $stmt->close();
                } catch (Exception $e) {}
            }
        } catch (Exception $e) {
            error_log('Error creando notificacion: ' . $e->getMessage());
        }
    }
    
    return $id;
}

function getMensajesGlobales($conn, $limit = 50) {
    $limit = (int)$limit;
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
    $limit = (int)$limit;
    $user_id = (int)$user_id;
    $otro_id = (int)$otro_id;
    $result = $conn->query("
        SELECT m.*, u.nombre as remitente_nombre, u.rol as remitente_rol
        FROM mensajes_internos m
        JOIN usuarios u ON m.remitente_id = u.id
        WHERE (m.remitente_id = $user_id AND m.destinatario_id = $otro_id)
           OR (m.remitente_id = $otro_id AND m.destinatario_id = $user_id)
        ORDER BY m.created_at DESC
        LIMIT $limit
    ");
    return $result;
}

function getConversaciones($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            CASE 
                WHEN m.remitente_id = ? THEN m.destinatario_id 
                ELSE m.remitente_id 
            END as otro_id,
            u.nombre as otro_nombre,
            u.rol as otro_rol
        FROM mensajes_internos m
        LEFT JOIN usuarios u ON u.id = CASE 
            WHEN m.remitente_id = ? THEN m.destinatario_id 
            ELSE m.remitente_id 
        END
        WHERE (m.remitente_id = ? OR m.destinatario_id = ?)
          AND m.destinatario_id IS NOT NULL
        ORDER BY m.created_at DESC
    ");
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversaciones = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['otro_id']) {
            $otro_id = $row['otro_id'];
            
            $stmt2 = $conn->prepare("SELECT mensaje, created_at FROM mensajes_internos 
                WHERE (remitente_id = ? AND destinatario_id = ?) OR (remitente_id = ? AND destinatario_id = ?) 
                ORDER BY created_at DESC LIMIT 1");
            $stmt2->bind_param("iiii", $user_id, $otro_id, $otro_id, $user_id);
            $stmt2->execute();
            $ultimo = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            
            $stmt3 = $conn->prepare("SELECT COUNT(*) as sin_leer FROM mensajes_internos 
                WHERE destinatario_id = ? AND remitente_id = ? AND leido = 0");
            $stmt3->bind_param("ii", $user_id, $otro_id);
            $stmt3->execute();
            $sin_leer = $stmt3->get_result()->fetch_assoc()['sin_leer'];
            $stmt3->close();
            
            $row['ultimo_mensaje'] = $ultimo['mensaje'] ?? '';
            $row['fecha_ultimo'] = $ultimo['created_at'] ?? '';
            $row['sin_leer'] = $sin_leer;
            $conversaciones[] = $row;
        }
    }
    $stmt->close();
    return $conversaciones;
}

function getMensajesNoLeidos($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM mensajes_internos WHERE destinatario_id = ? AND leido = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['total'] : 0;
}

function marcarMensajeLeido($conn, $mensaje_id, $user_id) {
    $stmt = $conn->prepare("UPDATE mensajes_internos SET leido = 1 WHERE id = ? AND destinatario_id = ?");
    $stmt->bind_param("ii", $mensaje_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

function getUsuariosParaChat($conn, $exclude_id = null) {
    if ($exclude_id) {
        $exclude_id = (int)$exclude_id;
        $result = $conn->query("SELECT id, nombre, rol FROM usuarios WHERE estado = 'activo' AND id != $exclude_id ORDER BY rol, nombre");
        return $result;
    } else {
        $result = $conn->query("SELECT id, nombre, rol FROM usuarios WHERE estado = 'activo' ORDER BY rol, nombre");
        return $result;
    }
}