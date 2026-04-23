<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nueva Garantía';
$error = '';
$success = '';

$orden_id = (int)$_GET['orden_id'];

$orden = getOrdenById($orden_id);
if (!$orden) {
    redirect('ordenes/listar.php');
}

$check_garantia = $conn->query("SELECT id FROM garantias WHERE orden_id = $orden_id");
if ($check_garantia->num_rows > 0) {
    $error = 'Esta orden ya tiene una garantía registrada';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $meses = (int)$_POST['meses'];
    $descripcion = sanitize($_POST['descripcion']);
    
    $fecha_inicio = date('Y-m-d');
    $fecha_fin = date('Y-m-d', strtotime("+{$meses} months"));
    
    $stmt = $conn->prepare("INSERT INTO garantias (orden_id, meses, fecha_inicio, fecha_fin, descripcion, estado) VALUES (?, ?, ?, ?, ?, 'activa')");
    $stmt->bind_param("iisss", $orden_id, $meses, $fecha_inicio, $fecha_fin, $descripcion);
    
    if ($stmt->execute()) {
        $success = 'Garantía creada correctamente';
        redirect('ordenes/ver.php?id=' . $orden_id);
    } else {
        $error = 'Error al crear la garantía';
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-shield-check"></i> Nueva Garantía</h1>
        <a href="../ordenes/ver.php?id=<?= $orden_id ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (!$error): ?>
    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Orden:</strong> <?= $orden['codigo'] ?></p>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Equipo:</strong> <?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) ?></p>
                    <p><strong>Costo Total:</strong> <?= formatMoney($orden['costo_total']) ?></p>
                </div>
            </div>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="meses" class="form-label">Período de Garantía *</label>
                            <select id="meses" name="meses" class="form-select" required>
                                <option value="1">1 mes</option>
                                <option value="2">2 meses</option>
                                <option value="3" selected>3 meses</option>
                                <option value="6">6 meses</option>
                                <option value="12">12 meses</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción de la garantía</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Describe los términos de la garantía..."></textarea>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    La garantía iniziará desde hoy (<?= date('d/m/Y') ?>) yتهاء en la fecha calculada según el período seleccionado.
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Crear Garantía
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once '../include/footer.php'; ?>