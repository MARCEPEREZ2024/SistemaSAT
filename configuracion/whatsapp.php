<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/whatsapp_helper.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

if (!isAdmin()) {
    redirect('../dashboard/index.php');
}

$page_title = 'WhatsApp';

$conn = getConnection();

$conn = getConnection();
$error = '';
$success = '';

$providers = [
    'twilio' => 'Twilio (oficial)',
    'wppconnect' => 'WPPConnect (self-hosted)',
    'chatapi' => 'Chat-API'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['provider'])) {
    $provider = $_POST['provider'];
    saveWhatsAppConfig('whatsapp_provider', $provider);
    
    if ($provider === 'twilio') {
        saveWhatsAppConfig('twilio_sid', $_POST['twilio_sid']);
        saveWhatsAppConfig('twilio_token', $_POST['twilio_token']);
        saveWhatsAppConfig('twilio_whatsapp_from', $_POST['twilio_whatsapp_from']);
    } elseif ($provider === 'chatapi') {
        saveWhatsAppConfig('chatapi_token', $_POST['chatapi_token']);
        saveWhatsAppConfig('chatapi_instance', $_POST['chatapi_instance']);
    } elseif ($provider === 'wppconnect') {
        saveWhatsAppConfig('wppconnect_url', $_POST['wppconnect_url']);
    }
    
    $success = 'Configuración guardada';
}

if (isset($_POST['test_whatsapp'])) {
    require_once '../include/whatsapp_helper.php';
    
    $telefono = preg_replace('/[^0-9]/', '', $_POST['test_phone']);
    $result = enviarWhatsApp($telefono, "🧪 Mensaje de prueba desde Sistema SAT", [
        'provider' => getWhatsAppConfig('whatsapp_provider')
    ]);
    
    if ($result['success']) {
        $success = 'Mensaje enviado correctamente';
    } else {
        $error = 'Error: ' . ($result['error'] ?? 'Desconocido');
    }
}

$currentProvider = getWhatsAppConfig('whatsapp_provider') ?: 'wppconnect';

require_once '../include/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-whatsapp"></i> Configuración WhatsApp</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Proveedor</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Proveedor de WhatsApp</label>
                            <select name="provider" id="providerSelect" class="form-select" onchange="toggleFields()">
                                <?php foreach ($providers as $key => $name): ?>
                                <option value="<?= $key ?>" <?= $currentProvider === $key ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="twilioFields" style="display: <?= $currentProvider === 'twilio' ? 'block' : 'none' ?>;">
                            <div class="mb-3">
                                <label class="form-label">Account SID</label>
                                <input type="text" name="twilio_sid" class="form-control" value="<?= htmlspecialchars(getWhatsAppConfig('twilio_sid') ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Auth Token</label>
                                <input type="password" name="twilio_token" class="form-control" value="<?= htmlspecialchars(getWhatsAppConfig('twilio_token') ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">WhatsApp From (sin whatsapp:)</label>
                                <input type="text" name="twilio_whatsapp_from" class="form-control" placeholder="+1234567890" value="<?= htmlspecialchars(getWhatsAppConfig('twilio_whatsapp_from') ?? '') ?>">
                            </div>
                        </div>
                        
                        <div id="chatapiFields" style="display: <?= $currentProvider === 'chatapi' ? 'block' : 'none' ?>;">
                            <div class="mb-3">
                                <label class="form-label">Token API</label>
                                <input type="password" name="chatapi_token" class="form-control" value="<?= htmlspecialchars(getWhatsAppConfig('chatapi_token') ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instance ID</label>
                                <input type="text" name="chatapi_instance" class="form-control" value="<?= htmlspecialchars(getWhatsAppConfig('chatapi_instance') ?? '') ?>">
                            </div>
                        </div>
                        
                        <div id="wppconnectFields" style="display: <?= $currentProvider === 'wppconnect' ? 'block' : 'none' ?>;">
                            <div class="mb-3">
                                <label class="form-label">URL del servidor WPPConnect</label>
                                <input type="text" name="wppconnect_url" class="form-control" placeholder="http://localhost:8080/sendMessage" value="<?= htmlspecialchars(getWhatsAppConfig('wppconnect_url') ?? 'http://localhost:8080/sendMessage') ?>">
                                <small class="text-muted">Debe tener WPPConnect Server ejecutándose</small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Configuración
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Prueba de Envío</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="test_whatsapp" value="1">
                        <div class="mb-3">
                            <label class="form-label">Número de teléfono</label>
                            <input type="text" name="test_phone" class="form-control" placeholder="51999999999" required>
                            <small class="text-muted">Sin + ni guiones</small>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send"></i> Enviar Mensaje de Prueba
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Información</h5>
                </div>
                <div class="card-body">
                    <h6>Twilio</h6>
                    <p class="small text-muted">Servicio oficial de WhatsApp Business API. Requiere cuenta pagada.</p>
                    
                    <h6>WPPConnect</h6>
                    <p class="small text-muted">Alternativa self-hosted. Ejecuta tu propio servidor WhatsApp.</p>
                    
                    <h6>Chat-API</h6>
                    <p class="small text-muted">Servicio alternativo con planes accesibles.</p>
                    
                    <hr>
                    <h6>Notificaciones Automáticas</h6>
                    <p class="small text-muted">El sistema puede enviar notificaciones cuando:</p>
                    <ul class="small">
                        <li>Se crea una nueva orden</li>
                        <li>El estado cambia</li>
                        <li>La orden está reparada</li>
                        <li>La orden es entregada</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFields() {
    const provider = document.getElementById('providerSelect').value;
    document.getElementById('twilioFields').style.display = provider === 'twilio' ? 'block' : 'none';
    document.getElementById('chatapiFields').style.display = provider === 'chatapi' ? 'block' : 'none';
    document.getElementById('wppconnectFields').style.display = provider === 'wppconnect' ? 'block' : 'none';
}
</script>
<?php require_once '../include/footer.php'; ?>