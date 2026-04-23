<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/csrf_helper.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nuevo Cliente';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido';
    } else {
        $nombre = sanitize($_POST['nombre']);
        $email = sanitize($_POST['email']);
        $telefono = sanitize($_POST['telefono']);
        $direccion = sanitize($_POST['direccion']);
        $dni = sanitize($_POST['dni']);
        
        $errors = get_validation_errors($_POST, [
            'nombre' => ['required' => true, 'max' => 100],
            'telefono' => ['required' => true, 'max' => 20],
            'email' => ['email' => true],
            'dni' => ['max' => 20]
        ]);
        
        if (!empty($errors)) {
            $error = implode(', ', $errors);
        } else {
            $stmt = $conn->prepare("INSERT INTO clientes (nombre, email, telefono, direccion, dni) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $email, $telefono, $direccion, $dni);
            
            if ($stmt->execute()) {
                $cliente_id = $conn->insert_id;
                
                require_once '../include/audit_helper.php';
                logClienteCreate($conn, $cliente_id, [
                    'nombre' => $nombre,
                    'email' => $email,
                    'telefono' => $telefono,
                    'dni' => $dni
                ]);
                
                redirect('clientes/listar.php');
            } else {
                $error = 'Error al registrar el cliente';
            }
        }
    }
}
csrf_token();
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
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <input type="text" id="telefono" name="telefono" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" id="dni" name="dni" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea id="direccion" name="direccion" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar Cliente
                </button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>