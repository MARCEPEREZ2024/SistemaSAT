<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/two_factor_helper.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Mi Perfil';

$usuario_id = $_SESSION['user_id'];
$conn = getConnection();

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'setup_2fa') {
            $qr = generarQR2FA($conn, $usuario_id);
            $success = '<strong>2FA Configurado:</strong> Escanee el código QR con Google Authenticator o authenticator, luego ingrese el código para activar.';
        } elseif ($_POST['action'] === 'verify_2fa') {
            $codigo = trim($_POST['codigo_2fa']);
            $result = verificarYActivar2FA($conn, $usuario_id, $codigo);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        } elseif ($_POST['action'] === 'disable_2fa') {
            $result = desactivar2FA($conn, $usuario_id);
            $success = '2FA desactivado correctamente';
        }
    } else {
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
    
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
}

require_once '../include/header.php';
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
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" disabled>
                            <small class="text-muted">El email no se puede cambiar</small>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" class="form-control" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol</label>
                            <input type="text" id="rol" class="form-control" value="<?= ucfirst($usuario['rol']) ?>" disabled>
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
                            <label for="password_actual" class="form-label">Contraseña Actual</label>
                            <input type="password" id="password_actual" name="password_actual" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                            <input type="password" id="password_nuevo" name="password_nuevo" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-lock"></i> Actualizar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Autenticación de Dos Factores (2FA)</h5>
                </div>
                <div class="card-body">
                    <?php if ($usuario['two_factor_enabled']): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 2FA está <strong>activado</strong>
                        </div>
                        <form method="POST" onsubmit="return confirm('¿Desactivar 2FA?');">
                            <input type="hidden" name="action" value="disable_2fa">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-x-circle"></i> Desactivar 2FA
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">Agregue una capa extra de seguridad a su cuenta usando Google Authenticator o cualquier aplicación TOTP.</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="setup_2fa">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-qr-code"></i> Configurar 2FA
                            </button>
                        </form>
                        
                        <?php if (isset($qr)): ?>
                        <hr>
                        <div class="text-center mb-3">
                            <img src="<?= $qr['qr'] ?>" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                        </div>
                        <div class="alert alert-info">
                            <strong>Clave secreta:</strong> <?= chunk_split($qr['secret'], 4, ' ') ?>
                        </div>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="verify_2fa">
                            <div class="mb-3">
                                <label for="codigo_2fa" class="form-label">Ingrese el código de 6 dígitos</label>
                                <input type="text" id="codigo_2fa" name="codigo_2fa" class="form-control" maxlength="6" pattern="[0-9]{6}" required placeholder="000000">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check"></i> Activar 2FA
                            </button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>