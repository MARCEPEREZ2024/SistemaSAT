<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/funciones.php';
require_once __DIR__ . '/../include/header.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$page_title = 'Búsqueda';

$busqueda = $_GET['q'] ?? '';
$resultados = [];

if ($busqueda) {
    // Buscar clientes
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE nombre LIKE ? OR email LIKE ? OR telefono LIKE ? OR dni LIKE ? LIMIT 20");
    $bus = "%$busqueda%";
    $stmt->bind_param("ssss", $bus, $bus, $bus, $bus);
    $stmt->execute();
    $resultados['clientes'] = $stmt->get_result();
    
    // Buscar órdenes
    $stmt = $conn->prepare("SELECT o.*, c.nombre as cliente_nombre, e.marca, e.modelo FROM ordenes_servicio o JOIN clientes c ON o.cliente_id = c.id JOIN equipos e ON o.equipo_id = e.id WHERE o.codigo LIKE ? OR c.nombre LIKE ? OR e.marca LIKE ? OR e.serie LIKE ? LIMIT 20");
    $bus2 = "%$busqueda%";
    $stmt->bind_param("ssss", $bus2, $bus2, $bus2, $bus2);
    $stmt->execute();
    $resultados['ordenes'] = $stmt->get_result();
    
    // Buscar equipos
    $stmt = $conn->prepare("SELECT e.*, c.nombre as cliente_nombre FROM equipos e JOIN clientes c ON e.cliente_id = c.id WHERE e.marca LIKE ? OR e.modelo LIKE ? OR e.serie LIKE ? LIMIT 20");
    $stmt->bind_param("sss", $bus, $bus, $bus);
    $stmt->execute();
    $resultados['equipos'] = $stmt->get_result();
}
?>
<div class="container-fluid">
    <h1><i class="bi bi-search"></i> Búsqueda Global</h1>
    
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Buscar por nombre, email, teléfono, DNI, código de orden, serie..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>
    
    <?php if($busqueda): ?>
    
    <?php if(isset($resultados['clientes']) && $resultados['clientes']->num_rows > 0): ?>
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">Clientes (<?= $resultados['clientes']->num_rows ?>)</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <?php while($c = $resultados['clientes']->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nombre']) ?></td>
                    <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($c['telefono']) ?></td>
                    <td><?= htmlspecialchars($c['dni'] ?? '-') ?></td>
                    <td><a href="clientes/ver.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Ver</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if(isset($resultados['ordenes']) && $resultados['ordenes']->num_rows > 0): ?>
    <div class="card mb-3">
        <div class="card-header bg-success text-white">Órdenes (<?= $resultados['ordenes']->num_rows ?>)</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <?php while($o = $resultados['ordenes']->fetch_assoc()): ?>
                <tr>
                    <td><?= $o['codigo'] ?></td>
                    <td><?= htmlspecialchars($o['cliente_nombre']) ?></td>
                    <td><?= htmlspecialchars($o['marca'].' '.$o['modelo']) ?></td>
                    <td><span class="badge" style="background:<?= COLORES_ESTADO[$o['estado']] ?? '#666' ?>"><?= ESTADOS_ORDEN[$o['estado']] ?? $o['estado'] ?></span></td>
                    <td><a href="ordenes/ver.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-primary">Ver</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if(isset($resultados['equipos']) && $resultados['equipos']->num_rows > 0): ?>
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark">Equipos (<?= $resultados['equipos']->num_rows ?>)</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <?php while($e = $resultados['equipos']->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($e['marca'].' '.$e['modelo']) ?></td>
                    <td><?= htmlspecialchars($e['cliente_nombre']) ?></td>
                    <td><?= htmlspecialchars($e['serie'] ?? '-') ?></td>
                    <td><a href="equipos/ver.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary">Ver</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if(empty($resultados['clientes']->num_rows) && empty($resultados['ordenes']->num_rows) && empty($resultados['equipos']->num_rows)): ?>
    <div class="alert alert-warning">No se encontraron resultados</div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../include/footer.php'; ?>