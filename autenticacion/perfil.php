<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Mi Perfil';

$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $telefono = sanitize($_POST['telefono']);
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nuevo = $_POST['password_nuevo'] ?? '';
    
    if ($password_actual && $password_nuevo) {
        if (!password_verify($password_actual, $usuario['password'])) {
            $error = 'La contraseña actual es incorrecta';
        } else {
            $password_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, telefono = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nombre, $telefono, $password_hash, $usuario_id);
        }
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $telefono, $usuario_id);
    }
    
    if (empty($error)) {
        if ($stmt->execute()) {
            $_SESSION['nombre'] = $nombre;
            $success = 'Perfil actualizado correctamente';
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Error al actualizar el perfil';
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-person-circle"></i> Mi Perfil</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Datos del Usuario</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" disabled>
                            <small class="text-muted">El email no se puede cambiar</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <input type="text" class="form-control" value="<?= ucfirst($usuario['rol']) ?>" disabled>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Cambiar Contraseña</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Contraseña Actual</label>
                            <input type="password" name="password_actual" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="password_nuevo" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-lock"></i> Actualizar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>