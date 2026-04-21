<?php
require_once '../config/database.php';
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['rol'] = $user['rol'];
        redirect('dashboard/index.php');
    } else {
        $error = 'Email o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema SAT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            <p class="mb-0">Servicio Técnico</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required placeholder="admin@sat.com">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required placeholder="password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                </button>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">Usuario: admin@sat.com | Pass: password</small>
            </div>
        </div>
    </div>
</body>
</html>