<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/funciones.php';
require_once __DIR__ . '/../include/header.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$page_title = 'Cambiar Estado';
$id = $_GET['id'] ?? 0;

$orden = getOrdenById($id);
if (!$orden) {
    redirect('ordenes/ver.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_estado = sanitize($_POST['estado']);
    $descripcion = sanitize($_POST['descripcion']);
    
    $estados_permitidos = ['recibido', 'en_diagnostico', 'en_reparacion', 'esperando_repuestos', 'reparado', 'entregado', 'cancelado'];
    
    if (!in_array($nuevo_estado, $estados_permitidos)) {
        $error = 'Estado no válido';
    } else {
        $stmt = $conn->prepare("UPDATE ordenes_servicio SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $id);
        
        if ($stmt->execute()) {
            $stmt = $conn->prepare("INSERT INTO estados_seguimiento (orden_id, estado, descripcion, tecnico_id, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("issi", $id, $nuevo_estado, $descripcion, $_SESSION['usuario_id']);
            $stmt->execute();
            
            $fecha_actualizar = '';
            switch ($nuevo_estado) {
                case 'en_diagnostico':
                    $fecha_actualizar = 'fecha_diagnostico';
                    break;
                case 'reparado':
                    $fecha_actualizar = 'fecha_reparacion';
                    break;
                case 'entregado':
                    $fecha_actualizar = 'fecha_entrega';
                    break;
            }
            
            if ($fecha_actualizar) {
                $stmt = $conn->prepare("UPDATE ordenes_servicio SET $fecha_actualizar = NOW() WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
            
            if ($nuevo_estado === 'reparado') {
                $stmt = $conn->prepare("UPDATE ordenes_servicio SET estado_orden = 'cerrada' WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
            
            if ($nuevo_estado === 'cancelado') {
                $stmt = $conn->prepare("UPDATE ordenes_servicio SET estado_orden = 'cancelada' WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
            
            sendNotification($orden['cliente_id'], $id, $nuevo_estado);
            
            $success = 'Estado actualizado correctamente';
            $orden = getOrdenById($id);
        } else {
            $error = 'Error al actualizar el estado';
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-arrow-repeat"></i> Cambiar Estado - <?= $orden['codigo'] ?></h1>
        <a href="ver.php?id=<?= $id ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Estado Actual</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge" style="background-color: <?= COLORES_ESTADO[$orden['estado']] ?? '#6c757d' ?>; font-size: 1.5rem; padding: 10px 20px;">
                        <?= ESTADOS_ORDEN[$orden['estado']] ?? $orden['estado'] ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Cambiar a</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nuevo Estado</label>
                            <select name="estado" class="form-select" required>
                                <?php foreach (ESTADOS_ORDEN as $key => $value): ?>
                                <option value="<?= $key ?>" <?= $orden['estado'] === $key ? 'selected' : '' ?>>
                                    <?= $value ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción del Cambio</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Observaciones del cambio de estado..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Actualizar Estado
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Flujo de Estados</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php 
                $flujo = ['recibido', 'en_diagnostico', 'en_reparacion', 'esperando_repuestos', 'reparado', 'entregado'];
                $actual_key = array_search($orden['estado'], $flujo);
                foreach ($flujo as $key => $estado): 
                ?>
                <div class="text-center">
                    <div class="badge" style="background-color: <?= COLORES_ESTADO[$estado] ?>; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 50%; <?= $key <= $actual_key ? 'opacity: 1;' : 'opacity: 0.4;' ?>">
                        <i class="bi bi-check"></i>
                    </div>
                    <small class="d-block mt-1"><?= ESTADOS_ORDEN[$estado] ?></small>
                </div>
                <?php if ($key < count($flujo) - 1): ?>
                <i class="bi bi-arrow-right align-self-center text-muted"></i>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>