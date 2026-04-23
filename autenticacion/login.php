<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/csrf_helper.php';
require_once '../include/two_factor_helper.php';

$conn = getConnection();

$error = '';
$success = '';
$login_attempts = $_SESSION['login_attempts'] ?? 0;
$lockout_time = $_SESSION['lockout_time'] ?? 0;

$step = $_SESSION['login_step'] ?? 1;
$pending_user_id = $_SESSION['pending_user_id'] ?? null;

if ($lockout_time > time()) {
    $remaining = $lockout_time - time();
    $error = 'Demasiados intentos. Intente novamente en ' . ceil($remaining / 60) . ' minuto(s)';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido';
    } elseif ($step == 2 && $pending_user_id) {
        $codigo = trim($_POST['codigo_2fa']);
        
        if (verificarCodigo2FA($_SESSION['pending_2fa_secret'], $codigo)) {
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $pending_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            completeLogin($user);
        } else {
            $error = 'Código inválido. Intente nuevamente';
        }
    } elseif ($step == 1) {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error = 'Email y contraseña son obligatorios';
        } else {
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['two_factor_enabled'] && $user['two_factor_secret']) {
                    $_SESSION['login_step'] = 2;
                    $_SESSION['pending_user_id'] = $user['id'];
                    $_SESSION['pending_2fa_secret'] = $user['two_factor_secret'];
                    $step = 2;
                } else {
                    completeLogin($user);
                }
            } else {
                $_SESSION['login_attempts'] = $login_attempts + 1;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['lockout_time'] = time() + 300;
                    $_SESSION['login_attempts'] = 0;
                    $error = 'Demasiados intentos. Bloqueado por 5 minutos';
                } else {
                    $error = 'Email o contraseña incorrectos. Intentos: ' . $_SESSION['login_attempts'] . '/5';
                }
                require_once '../include/audit_helper.php';
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($u = $result->fetch_assoc()) {
                    logLogin($conn, $u['id'], false);
                }
                $stmt->close();
            }
        }
    }
}

function completeLogin($user) {
    require_once '../include/sucursal_helper.php';
    require_once '../include/audit_helper.php';
    
    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
    $_SESSION['last_activity'] = time();
    
    logLogin(getConnection(), $user['id'], true);
    
    unset($_SESSION['login_step'], $_SESSION['pending_user_id'], $_SESSION['pending_2fa_secret']);
    
    getSucursalDefault(getConnection());
    
    redirect('dashboard/index.php');
}

csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema SAT</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet"> 
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 400px; width: 100%; }
        .login-header { background: #0d6efd; color: white; padding: 30px; border-radius: 15px 15px 0 0; text-align: center; }
        .login-body { padding: 30px; }
        .form-control { border-radius: 10px; padding: 12px; }
        .btn-login { border-radius: 10px; padding: 12px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-tools" style="font-size: 3rem;"></i>
            <h3 class="mt-2">Sistema SAT</h3>
            <p class="mb-0"><?= $step == 2 ? 'Verificación 2FA' : 'Servicio Técnico' ?></p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <?php if ($step == 2): ?>
                    <div class="mb-3">
                        <label class="form-label" for="codigo_2fa">Código de verificación</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="text" name="codigo_2fa" id="codigo_2fa" class="form-control" required placeholder="000000" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code">
                        </div>
                        <small class="text-muted">Ingrese el código de 6 dígitos de su app autenticadora</small>
                    </div>
                    <a href="login.php" class="btn btn-secondary mb-3">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" id="email" class="form-control" required placeholder="admin@sat.com" autocomplete="username">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="password" autocomplete="current-password">
                        </div>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> <?= $step == 2 ? 'Verificar' : 'Iniciar Sesión' ?>
                </button>
            </form>
            <?php if ($step == 1): ?>
            <div class="text-center mt-3">
                <small class="text-muted">Usuario: admin@sat.com | Pass: password</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>