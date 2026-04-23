<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Buscar';
$q = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all';

$results = [
    'ordenes' => [],
    'clientes' => [],
    'equipos' => [],
    'inventario' => []
];

if (strlen($q) >= 2) {
    $conn = getConnection();
    $search = "%" . $conn->real_escape_string($q) . "%";
    
    // Buscar órdenes
    $stmt = $conn->prepare("
        SELECT o.id, o.codigo, o.estado, c.nombre as cliente, e.marca, e.modelo 
        FROM ordenes_servicio o 
        LEFT JOIN clientes c ON o.cliente_id = c.id 
        LEFT JOIN equipos e ON o.equipo_id = e.id 
        WHERE o.codigo LIKE ? OR c.nombre LIKE ? OR e.marca LIKE ? OR e.modelo LIKE ?
        ORDER BY o.fecha_ingreso DESC LIMIT 10
    ");
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $results['ordenes'] = $stmt->get_result();
    
    // Buscar clientes
    $stmt = $conn->prepare("
        SELECT id, nombre, email, telefono 
        FROM clientes 
        WHERE nombre LIKE ? OR email LIKE ? OR telefono LIKE ? OR dni LIKE ?
        ORDER BY nombre ASC LIMIT 10
    ");
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $results['clientes'] = $stmt->get_result();
    
    // Buscar equipos
    $stmt = $conn->prepare("
        SELECT e.id, e.marca, e.modelo, e.serie, c.nombre as cliente
        FROM equipos e 
        LEFT JOIN clientes c ON e.cliente_id = c.id
        WHERE e.marca LIKE ? OR e.modelo LIKE ? OR e.serie LIKE ?
        ORDER BY e.fecha_ingreso DESC LIMIT 10
    ");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $results['equipos'] = $stmt->get_result();
    
    // Buscar inventario
    $stmt = $conn->prepare("
        SELECT id, codigo, nombre, stock, precio_venta
        FROM repuestos
        WHERE codigo LIKE ? OR nombre LIKE ? OR categoria LIKE ?
        ORDER BY nombre ASC LIMIT 10
    ");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $results['inventario'] = $stmt->get_result();
}

function getItemUrl($type, $id) {
    switch ($type) {
        case 'ordenes': return BASE_URL . 'ordenes/ver.php?id=' . $id;
        case 'clientes': return BASE_URL . 'clientes/ver.php?id=' . $id;
        case 'equipos': return BASE_URL . 'equipos/ver.php?id=' . $id;
        case 'inventario': return BASE_URL . 'inventario/ver.php?id=' . $id;
        default: return '#';
    }
}

function getItemIcon($type) {
    $icons = [
        'ordenes' => 'bi-ticket-detailed',
        'clientes' => 'bi-person',
        'equipos' => 'bi-laptop',
        'inventario' => 'bi-box-seam'
    ];
    return $icons[$type] ?? 'bi-search';
}
?>
<div class="container-fluid">
    <div class="mb-4">
        <h1><i class="bi bi-search"></i> Búsqueda Global</h1>
    </div>
    
    <form method="GET" class="mb-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="q" class="form-control" placeholder="Buscar por código, nombre, serie, email..." value="<?= htmlspecialchars($q) ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>
    
    <?php if ($q): ?>
    <div class="row">
        <?php foreach ($results as $type => $data): ?>
        <?php if ($data->num_rows > 0): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi <?= getItemIcon($type) ?>"></i> <?= ucfirst($type) ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php while ($row = $data->fetch_assoc()): ?>
                        <a href="<?= getItemUrl($type, $row['id']) ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($type === 'ordenes'): ?>
                                    <strong><?= $row['codigo'] ?></strong>
                                    <div class="text-muted small"><?= $row['cliente'] ?> (<?= $row['marca'] . ' ' . $row['modelo'] ?>)</div>
                                    <?php elseif ($type === 'clientes'): ?>
                                    <strong><?= $row['nombre'] ?></strong>
                                    <div class="text-muted small"><?= $row['email'] ?? '-' ?> | <?= $row['telefono'] ?></div>
                                    <?php elseif ($type === 'equipos'): ?>
                                    <strong><?= $row['marca'] . ' ' . $row['modelo'] ?></strong>
                                    <div class="text-muted small">Serie: <?= $row['serie'] ?? '-' ?></div>
                                    <?php else: ?>
                                    <strong><?= $row['codigo'] . ' - ' . $row['nombre'] ?></strong>
                                    <div class="text-muted small">Stock: <?= $row['stock'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <?php if (array_sum(array_map(function($d) { return $d->num_rows; }, $results)) === 0): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No se encontraron resultados para "<?= htmlspecialchars($q) ?>"
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once '../include/footer.php'; ?>