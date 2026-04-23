<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$conn = getConnection();

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

switch ($endpoint) {
    // Órdenes
    case 'ordenes':
        if ($method === 'GET') {
            if ($id > 0) {
                $stmt = $conn->prepare("
                    SELECT o.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono,
                           e.marca, e.modelo, e.serie, u.nombre as tecnico_nombre
                    FROM ordenes_servicio o
                    LEFT JOIN clientes c ON o.cliente_id = c.id
                    LEFT JOIN equipos e ON o.equipo_id = e.id
                    LEFT JOIN usuarios u ON o.tecnico_id = u.id
                    WHERE o.id = ?
                ");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $data = $stmt->get_result()->fetch_assoc();
                jsonResponse($data ?: ['error' => 'No encontrado']);
            } else {
                $estado = $_GET['estado'] ?? '';
                $sql = "SELECT o.*, c.nombre as cliente_nombre, e.marca, e.modelo 
                        FROM ordenes_servicio o 
                        LEFT JOIN clientes c ON o.cliente_id = c.id
                        LEFT JOIN equipos e ON o.equipo_id = e.id
                        WHERE 1=1";
                if ($estado) $sql .= " AND o.estado = '$estado'";
                $sql .= " ORDER BY o.fecha_ingreso DESC LIMIT 50";
                $result = $conn->query($sql);
                $data = [];
                while ($row = $result->fetch_assoc()) $data[] = $row;
                jsonResponse($data);
            }
        }
        break;
        
    case 'ordenes/crear':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $conn->prepare("
                INSERT INTO ordenes_servicio (codigo, cliente_id, equipo_id, tecnico_id, prioridad, estado, fecha_ingreso)
                VALUES (?, ?, ?, ?, 'recibido', NOW())
            ");
            $codigo = 'ORD-' . time();
            $stmt->bind_param("siii", $codigo, $data['cliente_id'], $data['equipo_id'], $data['tecnico_id'] ?? null);
            $stmt->execute();
            jsonResponse(['id' => $conn->insert_id, 'codigo' => $codigo], 201);
        }
        break;
        
    case 'ordenes/actualizar':
        if ($method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $conn->prepare("UPDATE ordenes_servicio SET estado = ?, diagnostico = ?, solucion = ?, costo_total = ? WHERE id = ?");
            $stmt->bind_param("sssdi", $data['estado'], $data['diagnostico'], $data['solucion'], $data['costo_total'], $id);
            $stmt->execute();
            jsonResponse(['success' => true]);
        }
        break;
        
    // Clientes
    case 'clientes':
        if ($method === 'GET') {
            if ($id > 0) {
                $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                jsonResponse($stmt->get_result()->fetch_assoc() ?: ['error' => 'No encontrado']);
            } else {
                $result = $conn->query("SELECT id, nombre, email, telefono FROM clientes WHERE estado = 'activo' ORDER BY nombre LIMIT 50");
                $data = [];
                while ($row = $result->fetch_assoc()) $data[] = $row;
                jsonResponse($data);
            }
        }
        break;
        
    // Equipos
    case 'equipos':
        if ($method === 'GET') {
            if ($id > 0) {
                $stmt = $conn->prepare("SELECT * FROM equipos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                jsonResponse($stmt->get_result()->fetch_assoc() ?: ['error' => 'No encontrado']);
            } else {
                $result = $conn->query("SELECT e.*, c.nombre as cliente_nombre FROM equipos e LEFT JOIN clientes c ON e.cliente_id = c.id ORDER BY e.fecha_ingreso DESC LIMIT 50");
                $data = [];
                while ($row = $result->fetch_assoc()) $data[] = $row;
                jsonResponse($data);
            }
        }
        break;
        
    // Inventario
    case 'inventario':
    case 'repuestos':
        if ($method === 'GET') {
            if ($id > 0) {
                $stmt = $conn->prepare("SELECT * FROM repuestos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                jsonResponse($stmt->get_result()->fetch_assoc() ?: ['error' => 'No encontrado']);
            } else {
                $result = $conn->query("SELECT * FROM repuestos WHERE estado = 'activo' ORDER BY nombre LIMIT 50");
                $data = [];
                while ($row = $result->fetch_assoc()) $data[] = $row;
                jsonResponse($data);
            }
        }
        break;
        
    // Facturas
    case 'facturas':
        if ($method === 'GET') {
            $result = $conn->query("SELECT f.*, c.nombre as cliente_nombre FROM facturas f LEFT JOIN clientes c ON f.cliente_id = c.id ORDER BY f.fecha_emision DESC LIMIT 50");
            $data = [];
            while ($row = $result->fetch_assoc()) $data[] = $row;
            jsonResponse($data);
        }
        break;
        
    // Dashboard stats
    case 'stats':
        $stats = [
            'ordenes' => [
                'total' => $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio")->fetch_assoc()['t'],
                'pendientes' => $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio WHERE estado NOT IN ('entregado', 'cancelado')")->fetch_assoc()['t'],
                'entregadas' => $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio WHERE estado = 'entregado'")->fetch_assoc()['t']
            ],
            'clientes' => [
                'total' => $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado = 'activo'")->fetch_assoc()['t']
            ],
            'inventario' => [
                'total' => $conn->query("SELECT COUNT(*) as t FROM repuestos WHERE estado = 'activo'")->fetch_assoc()['t'],
                'bajo_stock' => $conn->query("SELECT COUNT(*) as t FROM repuestos WHERE stock <= stock_minimo")->fetch_assoc()['t']
            ]
        ];
        jsonResponse($stats);
        break;
        
    // Buscar
    case 'buscar':
        $q = $_GET['q'] ?? '';
        if (strlen($q) < 2) jsonResponse([]);
        
        $search = "%$q%";
        $result = $conn->query("
            (SELECT 'orden' as tipo, id, codigo as titulo FROM ordenes_servicio WHERE codigo LIKE '$search')
            UNION
            (SELECT 'cliente' as tipo, id, nombre as titulo FROM clientes WHERE nombre LIKE '$search')
            UNION
            (SELECT 'equipo' as tipo, id, CONCAT(marca, ' ', modelo) as titulo FROM equipos WHERE marca LIKE '$search' OR modelo LIKE '$search')
            LIMIT 20
        ");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        jsonResponse($data);
        break;
        
    default:
        jsonResponse([
            'api' => 'Sistema SAT REST API',
            'version' => '1.0',
            'endpoints' => [
                'GET /api?endpoint=ordenes',
                'GET /api?endpoint=ordenes&id=1',
                'GET /api?endpoint=clientes',
                'GET /api?endpoint=equipos',
                'GET /api?endpoint=inventario',
                'GET /api?endpoint=facturas',
                'GET /api?endpoint=stats',
                'GET /api?endpoint=buscar&q=texto'
            ]
        ]);
}