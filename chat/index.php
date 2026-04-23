<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/chat_helper.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Chat Interno';
$conn = getConnection();
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mensaje'])) {
    $mensaje = trim($_POST['mensaje'] ?? '');
    $destinatario_id = !empty($_POST['destinatario_id']) ? (int)$_POST['destinatario_id'] : null;
    
    if (empty($mensaje)) {
        $error = 'Escriba un mensaje';
    } else {
        if (enviarMensajeInterno($conn, $user_id, $destinatario_id, $mensaje)) {
            $success = 'Mensaje enviado';
        } else {
            $error = 'Error al enviar mensaje';
        }
    }
}

$tipo = $_GET['tipo'] ?? 'global';
$otro_id = !empty($_GET['usuario']) ? (int)$_GET['usuario'] : null;

if ($tipo === 'global') {
    $mensajes = getMensajesGlobales($conn, 50);
    $titulo_chat = 'Mensajes Globales (Todos)';
} elseif ($otro_id) {
    $mensajes = getMensajesPrivados($conn, $user_id, $otro_id, 50);
    $stmt = $conn->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $otro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $otro = $result->fetch_assoc();
    $titulo_chat = 'Chat Privado: ' . htmlspecialchars($otro['nombre']);
    $stmt->close();
} else {
    $mensajes = getMensajesGlobales($conn, 50);
    $titulo_chat = 'Mensajes Globales (Todos)';
}

$usuarios = getUsuariosParaChat($conn, $user_id);
$conversaciones = getConversaciones($conn, $user_id);

require_once '../include/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-chat-dots"></i> Chat Interno</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?= $tipo === 'global' ? 'active' : '' ?>" href="?tipo=global">
                                <i class="bi bi-people"></i> Global
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#privados">
                                <i class="bi bi-person"></i> Privados
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <div class="tab-content">
                        <div class="tab-pane show <?= $tipo === 'global' ? 'active' : '' ?>" id="global">
                            <a href="?tipo=global" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $tipo === 'global' ? 'active' : '' ?>">
                                <div><i class="bi bi-people"></i> Mensajes Globales</div>
                                <span class="badge bg-primary">Todos</span>
                            </a>
                        </div>
                        
                        <div class="tab-pane" id="privados">
                            <?php foreach ($conversaciones as $c): if ($c['otro_id']): ?>
                            <a href="?tipo=privado&usuario=<?= $c['otro_id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $otro_id == $c['otro_id'] ? 'active' : '' ?>">
                                <div>
                                    <strong><?= htmlspecialchars($c['otro_nombre']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($c['otro_rol']) ?></small>
                                </div>
                                <?php if ($c['sin_leer'] > 0): ?>
                                <span class="badge bg-danger"><?= $c['sin_leer'] ?></span>
                                <?php endif; ?>
                            </a>
                            <?php endif; endforeach; ?>
                            
                            <?php if (count($conversaciones) == 0): ?>
                            <div class="p-3 text-muted text-center">Sin conversaciones</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Nueva Conversación</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="enviar_mensaje" value="1">
                        <div class="mb-2">
                            <select name="destinatario_id" class="form-select form-select-sm">
                                <option value="">-- Todos (Global) --</option>
                                <?php while ($u = $usuarios->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?> (<?= $u['rol'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea name="mensaje" class="form-control form-control-sm" rows="2" placeholder="Escribe tu mensaje..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-send"></i> Enviar
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-<?= $tipo === 'global' ? 'text' : 'quote' ?>"></i> <?= $titulo_chat ?></h5>
                    <?php
if ($tipo !== 'global' && $otro_id) {
    $stmt = $conn->prepare("UPDATE mensajes_internos SET leido = 1 WHERE destinatario_id = ? AND remitente_id = ?");
    $stmt->bind_param("ii", $user_id, $otro_id);
    $stmt->execute();
    $stmt->close();
}
if ($tipo !== 'global' && $otro_id): ?>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body" style="height: 400px; overflow-y: auto;" id="chatMessages">
                    <?php if ($mensajes && $mensajes->num_rows > 0): ?>
                        <?php while ($m = $mensajes->fetch_assoc()): ?>
                        <div class="mb-3 <?= $m['remitente_id'] == $user_id ? 'text-end' : '' ?>">
                            <div class="d-inline-block <?= $m['remitente_id'] == $user_id ? 'bg-primary text-white' : 'bg-light' ?> p-2 rounded" style="max-width: 80%;">
                                <div class="small fw-bold <?= $m['remitente_id'] == $user_id ? 'text-white-50' : 'text-muted' ?>">
                                    <?= htmlspecialchars($m['remitente_nombre']) ?>
                                </div>
                                <div><?= nl2br(htmlspecialchars($m['mensaje'])) ?></div>
                                <div class="small <?= $m['remitente_id'] == $user_id ? 'text-white-50' : 'text-muted' ?>">
                                    <?= date('d/m H:i', strtotime($m['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                            <p class="mt-2">No hay mensajes aún</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer">
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="enviar_mensaje" value="1">
                        <?php if ($tipo !== 'global'): ?>
                        <input type="hidden" name="destinatario_id" value="<?= $otro_id ?>">
                        <?php endif; ?>
                        <input type="text" name="mensaje" class="form-control" placeholder="Escribe un mensaje..." required>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
setInterval(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tipo = urlParams.get('tipo') || 'global';
    const usuario = urlParams.get('usuario') || '';
    
    fetch('<?= BASE_URL ?>api/chat_messages.php?tipo=' + tipo + '&usuario=' + usuario, {cache: 'no-cache'})
        .then(function(r) { 
            if (!r.ok) throw new Error('Network error');
            return r.text(); 
        })
        .then(function(html) {
            document.getElementById('chatMessages').innerHTML = html;
        })
        .catch(function(e) { 
            console.log('Chat refresh:', e.message); 
        });
}, 10000);
</script>
<?php require_once '../include/footer.php'; ?>