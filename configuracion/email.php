<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Configuración de Email';

$config = [
    'smtp_host' => getConfig('smtp_host'),
    'smtp_port' => getConfig('smtp_port'),
    'smtp_user' => getConfig('smtp_user'),
    'smtp_pass' => getConfig('smtp_pass'),
    'smtp_from_email' => getConfig('smtp_from_email'),
    'smtp_from_name' => getConfig('smtp_from_name'),
    'smtp_secure' => getConfig('smtp_secure')
];

$success = '';
$error = '';

$smtp_user_saved = getConfig('smtp_user');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $conn = getConnection();
    
    $smtp_host = sanitize($_POST['smtp_host']);
    $smtp_port = sanitize($_POST['smtp_port']);
    $smtp_user = sanitize($_POST['smtp_user']);
    $smtp_pass = sanitize($_POST['smtp_pass']);
    $smtp_from_email = sanitize($_POST['smtp_from_email']);
    $smtp_from_name = sanitize($_POST['smtp_from_name']);
    $smtp_secure = sanitize($_POST['smtp_secure'] ?? 'tls');
    
    $conn->query("DELETE FROM configuraciones WHERE clave LIKE 'smtp_%'");

    $configs = [
        ['smtp_host', $smtp_host],
        ['smtp_port', $smtp_port],
        ['smtp_user', $smtp_user],
        ['smtp_pass', $smtp_pass],
        ['smtp_from_email', $smtp_from_email],
        ['smtp_from_name', $smtp_from_name],
        ['smtp_secure', $smtp_secure]
    ];
    
    $stmt = $conn->prepare("INSERT INTO configuraciones (clave, valor) VALUES (?, ?)");
    foreach ($configs as $config) {
        $stmt->bind_param("ss", $config[0], $config[1]);
        $stmt->execute();
    }
    $stmt->close();
    
    $config = [
        'smtp_host' => $smtp_host,
        'smtp_port' => $smtp_port,
        'smtp_user' => $smtp_user,
        'smtp_pass' => $smtp_pass,
        'smtp_from_email' => $smtp_from_email,
        'smtp_from_name' => $smtp_from_name,
        'smtp_secure' => $smtp_secure
    ];
    
    $success = 'Configuración guardada';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email_action'])) {
    $test_email = trim($_POST['test_email_dest'] ?? '');
    
    $smtp_host = getConfig('smtp_host');
    $smtp_user = getConfig('smtp_user');
    $from_email = getConfig('smtp_from_email');
    
    // Debug
    $debug_info = "Host: '$smtp_host' | User: '$smtp_user' | From: '$from_email' | Dest: '$test_email'";
    error_log($debug_info);
    
    if (empty($smtp_host)) {
        $error = 'Servidor SMTP no configurado';
    } elseif (empty($smtp_user)) {
        $error = 'Usuario SMTP no configurado';
    } elseif (empty($test_email)) {
        $error = 'Ingrese un email de destino';
    } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email de destino no es válido';
    } else {
        try {
            $mail = getConfiguredMailer();
            
            if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email remitente no configurado correctamente');
            }
            
            $mail->addAddress($test_email);
            $mail->Subject = 'Test Sistema SAT';
            $mail->Body = '<h1>Email de Prueba</h1><p>Este es un email de prueba del sistema SAT.</p>';
            
            $mail->send();
            $success = 'Email de prueba enviado correctamente a: ' . htmlspecialchars($test_email);
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

function getConfiguredMailer() {
    require_once  '../vendor/autoload.php';
    
    $smtp_host = getConfig('smtp_host') ?: 'smtp.gmail.com';
    $smtp_port = getConfig('smtp_port') ?: '587';
    $smtp_user = getConfig('smtp_user') ?: '';
    $smtp_pass = getConfig('smtp_pass') ?: '';
    $from_email = getConfig('smtp_from_email') ?: $smtp_user;
    $from_name = getConfig('smtp_from_name') ?: 'Servicio Técnico SAT';
    $smtp_secure = getConfig('smtp_secure') ?: 'tls';
    
    if (empty($smtp_user) || empty($from_email)) {
        throw new Exception('SMTP no configurado correctamente');
    }
    
    $mail = new PHPMailer(true);
    
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_user;
    $mail->Password = $smtp_pass;
    $mail->SMTPSecure = $smtp_secure;
    $mail->Port = $smtp_port;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom($from_email, $from_name);
    
    return $mail;
}

function sendEmailSMTP($to, $subject, $body, $isHTML = true) {
    try {
        $mail = getConfiguredMailer();
        $mail->addAddress($to);
        $mail->Subject = $subject;
        
        if ($isHTML) {
            $mail->isHTML(true);
            $mail->Body = $body;
        } else {
            $mail->isHTML(false);
            $mail->Body = strip_tags($body);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-envelope-at"></i> Configuración SMTP</h1>
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
                                    <label for="smtp_host" class="form-label">Servidor SMTP</label>
                                    <input type="text" id="smtp_host" name="smtp_host" class="form-control" value="<?= htmlspecialchars($config['smtp_host'] ?? 'smtp.gmail.com') ?>" placeholder="smtp.gmail.com">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">Puerto</label>
                                    <input type="text" id="smtp_port" name="smtp_port" class="form-control" value="<?= htmlspecialchars($config['smtp_port'] ?? '587') ?>" placeholder="587">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="smtp_secure" class="form-label">Seguridad</label>
                                    <select id="smtp_secure" name="smtp_secure" class="form-select">
                                        <option value="tls" <?= ($config['smtp_secure'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= ($config['smtp_secure'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_user" class="form-label">Usuario SMTP</label>
                                    <input type="text" id="smtp_user" name="smtp_user" class="form-control" value="<?= htmlspecialchars($config['smtp_user'] ?? '') ?>" placeholder="tu@email.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_pass" class="form-label">Contraseña SMTP</label>
                                    <input type="password" id="smtp_pass" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($config['smtp_pass'] ?? '') ?>">
                                    <small class="text-muted">Para Gmail usa App Password</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_from_email" class="form-label">Email Remitente</label>
                                    <input type="email" id="smtp_from_email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($config['smtp_from_email'] ?? '') ?>" placeholder="tu@email.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_from_name" class="form-label">Nombre Remitente</label>
                                    <input type="text" id="smtp_from_name" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($config['smtp_from_name'] ?? 'Servicio Técnico SAT') ?>">
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
                        <input type="hidden" name="test_email_action" value="1">
                        <input type="email" name="test_email_dest" class="form-control" placeholder="email@destino.com" required>
                        <button type="submit" class="btn btn-outline-primary">
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
                        <li>Puerto: <code>587</code> (TLS) o <code>465</code> (SSL)</li>
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
                    <h6>Yahoo:</h6>
                    <ul>
                        <li>Servidor: <code>smtp.mail.yahoo.com</code></li>
                        <li>Puerto: <code>587</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once  '../include/footer.php'; ?>