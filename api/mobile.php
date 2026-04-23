<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAuth() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (!$token) {
        respond(['success' => false, 'error' => 'Token requerido'], 401);
    }
    
    $token = str_replace('Bearer ', '', $token);
    
    // Simple token validation (in production use JWT)
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE MD5(CONCAT(id, password)) = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        return $user;
    }
    
    respond(['success' => false, 'error' => 'Token inválido'], 401);
}

switch ($action) {
    case 'login':
        if ($method !== 'POST') {
            respond(['success' => false, 'error' => 'Método no permitido'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        if (!$email || !$password) {
            respond(['success' => false, 'error' => 'Email y contraseña requeridos']);
        }
        
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? AND estado = 'activo'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Generate simple token
                $token = md5($user['id'] . $user['password'] . time());
                
                respond([
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'nombre' => $user['nombre'],
                        'email' => $user['email'],
                        'rol' => $user['rol']
                    ]
                ]);
            }
        }
        
        respond(['success' => false, 'error' => 'Credenciales inválidas']);
        break;
        
    case 'ordenes':
        $user = requireAuth();
        $conn = getConnection();
        
        $estado = $_GET['estado'] ?? '';
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $where = "1=1";
        if ($estado) {
            $where .= " AND o.estado = '$estado'";
        }
        
        if ($user['rol'] === 'tecnico') {
            $where .= " AND o.tecnico_id = " . $user['id'];
        }
        
        $sql = "SELECT o.id, o.codigo, o.estado, o.prioridad, o.fecha_ingreso, o.costo_total,
                       c.nombre as cliente_nombre, c.telefono as cliente_telefono,
                       e.marca, e.modelo, e.serie
                FROM ordenes_servicio o
                LEFT JOIN clientes c ON o.cliente_id = c.id
                LEFT JOIN equipos e ON o.equipo_id = e.id
                WHERE $where
                ORDER BY o.fecha_ingreso DESC
                LIMIT $limit OFFSET $offset";
        
        $result = $conn->query($sql);
        $ordenes = [];
        
        while ($row = $result->fetch_assoc()) {
            $ordenes[] = $row;
        }
        
        respond(['success' => true, 'data' => $ordenes]);
        break;
        
    case 'orden_detalle':
        $user = requireAuth();
        $id = (int)$_GET['id'] ?? 0;
        
        if (!$id) {
            respond(['success' => false, 'error' => 'ID requerido']);
        }
        
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT o.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email,
                   e.marca, e.modelo, e.serie, e.tipo_equipo,
                   u.nombre as tecnico_nombre
            FROM ordenes_servicio o
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN equipos e ON o.equipo_id = e.id
            LEFT JOIN usuarios u ON o.tecnico_id = u.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($orden = $result->fetch_assoc()) {
            // Get history
            $historial = $conn->query("
                SELECT * FROM estados_seguimiento 
                WHERE orden_id = $id 
                ORDER BY fecha DESC
            ");
            
            $historial_array = [];
            while ($h = $historial->fetch_assoc()) {
                $historial_array[] = $h;
            }
            
            $orden['historial'] = $historial_array;
            
            respond(['success' => true, 'data' => $orden]);
        }
        
        respond(['success' => false, 'error' => 'Orden no encontrada']);
        break;
        
    case 'cambiar_estado':
        $user = requireAuth();
        
        if ($method !== 'POST') {
            respond(['success' => false, 'error' => 'Método no permitido'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)$data['id'] ?? 0;
        $estado = sanitize($data['estado'] ?? '');
        $nota = sanitize($data['nota'] ?? '');
        
        if (!$id || !$estado) {
            respond(['success' => false, 'error' => 'ID y estado requeridos']);
        }
        
        $conn = getConnection();
        
        $stmt = $conn->prepare("UPDATE ordenes_servicio SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?");
        $stmt->bind_param("si", $estado, $id);
        
        if ($stmt->execute()) {
            // Add history
            $stmt2 = $conn->prepare("INSERT INTO estados_seguimiento (orden_id, estado, descripcion, tecnico_id, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmt2->bind_param("issi", $id, $estado, $nota, $user['id']);
            $stmt2->execute();
            
            respond(['success' => true, 'message' => 'Estado actualizado']);
        }
        
        respond(['success' => false, 'error' => 'Error al actualizar']);
        break;
        
    case 'clientes':
        $user = requireAuth();
        $conn = getConnection();
        
        $search = $_GET['search'] ?? '';
        $limit = (int)($_GET['limit'] ?? 50);
        
        $where = "c.estado = 'activo'";
        if ($search) {
            $where .= " AND (c.nombre LIKE '%$search%' OR c.telefono LIKE '%$search%' OR c.dni LIKE '%$search%')";
        }
        
        $sql = "SELECT c.id, c.nombre, c.email, c.telefono, c.dni, c.direccion,
                       COUNT(o.id) as ordenes_count
                FROM clientes c
                LEFT JOIN ordenes_servicio o ON c.id = o.cliente_id
                WHERE $where
                GROUP BY c.id
                ORDER BY c.nombre
                LIMIT $limit";
        
        $result = $conn->query($sql);
        $clientes = [];
        
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
        
        respond(['success' => true, 'data' => $clientes]);
        break;
        
    case 'stats':
        $user = requireAuth();
        $conn = getConnection();
        
        $stats = [];
        
        $result = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE estado NOT IN ('entregado', 'cancelado')");
        $stats['pendientes'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE estado = 'entregado'");
        $stats['entregadas'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM clientes");
        $stats['clientes'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE DATE(fecha_ingreso) = CURDATE()");
        $stats['hoy'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        respond(['success' => true, 'data' => $stats]);
        break;
        
    default:
        respond([
            'success' => false,
            'error' => 'Acción no válida',
            'actions' => ['login', 'ordenes', 'orden_detalle', 'cambiar_estado', 'clientes', 'stats']
        ]);
}