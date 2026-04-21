<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/funciones.php';
require_once __DIR__ . '/../include/header.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$page_title = 'Notificaciones';

$success = '';
$error = '';
$orden_id_seleccionada = 0;

// Cargar datos de la orden
if (isset($_POST['cargar_datos'])) {
    $orden_id_seleccionada = (int)$_POST['orden_id'];
    $_SESSION['orden_seleccionada'] = $orden_id_seleccionada;
}

// Enviar email
if (isset($_POST['enviar_email'])) {
    $orden_id_seleccionada = (int)$_POST['orden_id'];
    $asunto = sanitize($_POST['asunto']);
    $mensaje = sanitize($_POST['mensaje']);
    
    if (empty($asunto) || empty($mensaje)) {
        $error = 'Escribe el asunto y el mensaje';
    } else {
        $orden = getOrdenById($orden_id_seleccionada);
        if ($orden && !empty($orden['cliente_email'])) {
            $headers = "From: servicio@sat.com\r\nContent-Type: text/html; charset=UTF-8\r\n";
            $cuerpo = "<html><body><h2>Hola {$orden['cliente_nombre']},</h2><p>{$mensaje}</p><hr><p><strong>Orden:</strong> {$orden['codigo']}<br><strong>Equipo:</strong> {$orden['marca']} {$orden['modelo']}</p></body></html>";
            
            if (mail($orden['cliente_email'], $asunto, $cuerpo, $headers)) {
                logNotification($orden['cliente_id'], $orden_id_seleccionada, 'estado', 'email', $asunto);
                $success = 'Email enviado correctamente';
            } else {
                logNotification($orden['cliente_id'], $orden_id_seleccionada, 'estado', 'email', $asunto);
                $success = 'Email guardado en logs';
            }
        } else {
            $error = 'Cliente sin email registrado';
        }
    }
}

$orden_id_seleccionada = $_SESSION['orden_seleccionada'] ?? 0;
$orden_seleccionada = $orden_id_seleccionada > 0 ? getOrdenById($orden_id_seleccionada) : null;
$ordenes = getAllOrdenes();
?>
<div class="container-fluid">
    <h1><i class="bi bi-envelope"></i> Notificaciones a Clientes</h1>
    
    <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    
    <form method="POST">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Seleccionar Orden</div>
                <div class="card-body p-0">
                    <select name="orden_id" size="15" style="height:350px;">
                        <option value="">-- Seleccionar --</option>
                        <?php while($o = $ordenes->fetch_assoc()): ?>
                        <option value="<?= $o['id'] ?>" <?= ($orden_id_seleccionada == $o['id']) ? 'selected' : '' ?>><?= $o['codigo'] ?> - <?= htmlspecialchars($o['cliente_nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="cargar_datos" class="btn btn-primary w-100 mt-2">Cargar Datos</button>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if($orden_seleccionada): ?>
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">Datos del Cliente</div>
                <div class="card-body">
                    <p class="mb-1"><strong>Cliente:</strong> <?= htmlspecialchars($orden_seleccionada['cliente_nombre']) ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($orden_seleccionada['cliente_email'] ?? 'Sin email') ?></p>
                    <p class="mb-1"><strong>Orden:</strong> <?= htmlspecialchars($orden_seleccionada['codigo']) ?></p>
                    <p class="mb-0"><strong>Equipo:</strong> <?= htmlspecialchars($orden_seleccionada['marca'] . ' ' . $orden_seleccionada['modelo']) ?></p>
                </div>
            </div>
            <?php else: ?>
            <div class="card mb-3">
                <div class="card-body text-center text-muted py-5">
                    Selecciona una orden y presiona "Cargar Datos"
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">Redactar Email</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Asunto</label>
                        <input type="text" name="asunto" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mensaje</label>
                        <textarea name="mensaje" class="form-control" rows="6"></textarea>
                    </div>
                    
                    <button type="submit" name="enviar_email" class="btn btn-success">Enviar Email</button>
                </div>
            </div>
        </div>
    </div>
    </form>
</div>
<?php require_once __DIR__ . '/../include/footer.php'; ?>