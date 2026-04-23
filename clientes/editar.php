<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Editar Cliente';
$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

if (!$cliente) {
    redirect('clientes/listar.php');
}

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
        $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, email = ?, telefono = ?, direccion = ?, dni = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nombre, $email, $telefono, $direccion, $dni, $id);
        
        if ($stmt->execute()) {
                require_once '../include/audit_helper.php';
                logClienteUpdate($conn, $id, $cliente, $_POST);
                
                redirect('clientes/listar.php');
        } else {
            $error = 'Error al actualizar el cliente';
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-pencil"></i> Editar Cliente</h1>
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
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <input type="text" id="telefono" name="telefono" class="form-control" value="<?= htmlspecialchars($cliente['telefono']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" id="dni" name="dni" class="form-control" value="<?= htmlspecialchars($cliente['dni'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea id="direccion" name="direccion" class="form-control" rows="2"><?= htmlspecialchars($cliente['direccion'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Actualizar Cliente
                </button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>