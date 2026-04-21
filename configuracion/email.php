<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/funciones.php';
require_once __DIR__ . '/../include/header.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$page_title = 'Configuración de Email';

$config = [
    'smtp_host' => getConfig('smtp_host'),
    'smtp_port' => getConfig('smtp_port'),
    'smtp_user' => getConfig('smtp_user'),
    'smtp_pass' => getConfig('smtp_pass'),
    'smtp_from_email' => getConfig('smtp_from_email'),
    'smtp_from_name' => getConfig('smtp_from_name')
];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $conn = getConnection();
    
    $smtp_host = sanitize($_POST['smtp_host']);
    $smtp_port = sanitize($_POST['smtp_port']);
    $smtp_user = sanitize($_POST['smtp_user']);
    $smtp_pass = sanitize($_POST['smtp_pass']);
    $smtp_from_email = sanitize($_POST['smtp_from_email']);
    $smtp_from_name = sanitize($_POST['smtp_from_name']);
    
    $conn->query("DELETE FROM configuraciones WHERE clave LIKE 'smtp_%'");
    $conn->query("INSERT INTO configuraciones (clave, valor) VALUES ('smtp_host', '$smtp_host')");
    $conn->query("INSERT INTO configuraciones (clave, valor) VALUES ('smtp_port', '$smtp_port')");
    $conn->query("INSERT INTO configuraciones (clave, valor) VALUES ('smtp_user', '$smtp_user')");
    $conn->query("INSERT INTO configuraciones (clave, valor) VALUES ('smtp_pass', '$smtp_pass')");
    $conn->query("INSERT INTO configuraciones (clave, valor) VALUES ('smtp_from_email', '$smtp_from_email')");
    $conn->query("INSERT INTO configuraciones (clave, valor) VALUES ('smtp_from_name', '$smtp_from_name')");
    
    $success = 'Configuración guardada';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $test_email = sanitize($_POST['test_email']);
    $result = sendEmailSMTP($test_email, 'Test Sistema SAT', 'Este es un email de prueba del sistema SAT.');
    if ($result === true) {
        $success = 'Email de prueba enviado';
    } else {
        $error = 'Error: ' . $result;
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-gear"></i> Configuración SMTP</h1>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Configuración del Servidor SMTP</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Servidor SMTP</label>
                                    <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($config['smtp_host'] ?? 'smtp.gmail.com') ?>" placeholder="smtp.gmail.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Puerto</label>
                                    <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($config['smtp_port'] ?? '587') ?>" placeholder="587">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Usuario SMTP</label>
                                    <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($config['smtp_user'] ?? '') ?>" placeholder="tu@email.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contraseña SMTP</label>
                                    <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($config['smtp_pass'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Remitente</label>
                                    <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($config['smtp_from_email'] ?? '') ?>" placeholder="tu@email.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre Remitente</label>
                                    <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($config['smtp_from_name'] ?? 'Servicio Técnico SAT') ?>">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="guardar_config" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Configuración
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Probar Email</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="d-flex gap-2">
                        <input type="email" name="test_email" class="form-control" placeholder="email@destino.com" required>
                        <button type="submit" name="test_email" class="btn btn-outline-primary">
                            <i class="bi bi-send"></i> Enviar Prueba
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Cómo obtener datos SMTP</h5>
                </div>
                <div class="card-body">
                    <h6>Gmail:</h6>
                    <ul>
                        <li>Servidor: <code>smtp.gmail.com</code></li>
                        <li>Puerto: <code>587</code></li>
                        <li>Usuario: Tu email completo</li>
                        <li>Contraseña: <a href="https://support.google.com/accounts/answer/185833" target="_blank">Crear App Password</a></li>
                    </ul>
                    <hr>
                    <h6>Outlook:</h6>
                    <ul>
                        <li>Servidor: <code>smtp.office365.com</code></li>
                        <li>Puerto: <code>587</code></li>
                    </ul>
                    <hr>
                    <h6>Mailgun/SendGrid:</h6>
                    <ul>
                        <li>Usar los datos del panel</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../include/footer.php'; ?>

<?php
function sendEmailSMTP($to, $subject, $body, $isHTML = true) {
    $smtp_host = getConfig('smtp_host');
    $smtp_port = getConfig('smtp_port');
    $smtp_user = getConfig('smtp_user');
    $smtp_pass = getConfig('smtp_pass');
    $from_email = getConfig('smtp_from_email');
    $from_name = getConfig('smtp_from_name');
    
    if (empty($smtp_host) || empty($smtp_user)) {
        return 'SMTP no configurado';
    }
    
    $conn = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
    if (!$conn) {
        return "No se pudo conectar al servidor SMTP: $errstr";
    }
    
    $resp = fgets($conn, 515);
    if (substr($resp, 0, 3) != '220') {
        return 'Error de conexión SMTP';
    }
    
    fputs($conn, "EHLO " . $smtp_host . "\r\n");
    $resp = fgets($conn, 515);
    
    fputs($conn, "AUTH LOGIN\r\n");
    fgets($conn, 335);
    
    fputs($conn, base64_encode($smtp_user) . "\r\n");
    fgets($conn, 334);
    
    fputs($conn, base64_encode($smtp_pass) . "\r\n");
    $resp = fgets($conn, 515);
    
    if (substr($resp, 0, 3) != '235') {
        return 'Autenticación fallida';
    }
    
    fputs($conn, "MAIL FROM:<$from_email>\r\n");
    fgets($conn, 250);
    
    fputs($conn, "RCPT TO:<$to>\r\n");
    fgets($conn, 250);
    
    fputs($conn, "DATA\r\n");
    fgets($conn, 354);
    
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    $headers .= "\r\n$body\r\n.\r\n";
    
    fputs($conn, $headers);
    $resp = fgets($conn, 515);
    
    fputs($conn, "QUIT\r\n");
    fclose($conn);
    
    if (substr($resp, 0, 3) == '250') {
        return true;
    }
    
    return 'Error al enviar';
}
?>