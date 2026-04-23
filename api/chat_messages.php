<?php
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/funciones.php';
require_once __DIR__ . '/../include/chat_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';

if ($action === 'contar') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['logged_in']) && !isset($_SESSION['usuario_id'])) {
        echo json_encode(['no_leidos' => 0]);
        exit;
    }
    $conn = getConnection();
    $user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'];
    $no_leidos = getMensajesNoLeidos($conn, $user_id);
    echo json_encode(['no_leidos' => $no_leidos]);
    exit;
}

if (!isset($_SESSION['logged_in'])) {
    echo '<div class="text-center text-muted p-3">Sesión no válida</div>';
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

$tipo = $_GET['tipo'] ?? 'global';
$otro_id = !empty($_GET['usuario']) ? (int)$_GET['usuario'] : null;

if ($tipo === 'global') {
    $mensajes = getMensajesGlobales($conn, 50);
    $titulo = 'Mensajes Globales';
} elseif ($otro_id) {
    $mensajes = getMensajesPrivados($conn, $user_id, $otro_id, 50);
    $stmt = $conn->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $otro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $otro = $result->fetch_assoc();
    $titulo = 'Chat: ' . ($otro['nombre'] ?? '');
    $stmt->close();
} else {
    $mensajes = getMensajesGlobales($conn, 50);
    $titulo = 'Mensajes Globales';
}

if ($mensajes && $mensajes->num_rows > 0):
    while ($m = $mensajes->fetch_assoc()): ?>
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
<?php endif;