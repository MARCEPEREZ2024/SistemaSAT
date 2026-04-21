<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nuevo Cliente';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $email = sanitize($_POST['email']);
    $telefono = sanitize($_POST['telefono']);
    $direccion = sanitize($_POST['direccion']);
    $dni = sanitize($_POST['dni']);
    
    if (empty($nombre) || empty($telefono)) {
        $error = 'El nombre y teléfono son obligatorios';
    } else {
        $stmt = $conn->prepare("INSERT INTO clientes (nombre, email, telefono, direccion, dni) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $email, $telefono, $direccion, $dni);
        
        if ($stmt->execute()) {
            redirect('clientes/listar.php');
        } else {
            $error = 'Error al registrar el cliente';
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-person-plus"></i> Nuevo Cliente</h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Teléfono *</label>
                            <input type="text" name="telefono" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <textarea name="direccion" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar Cliente
                </button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>