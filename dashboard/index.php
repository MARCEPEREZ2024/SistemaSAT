<?php
require_once   '../config/database.php';
require_once  '../config/config.php';
require_once  '../include/funciones.php';
require_once  '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Dashboard';

// Obtener estadísticas
$ordenes_total = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio")->fetch_assoc()['total'] ?? 0;
$ordenes_abiertas = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE estado NOT IN ('entregado', 'cancelado')")->fetch_assoc()['total'] ?? 0;
$ordenes_entregadas = $conn->query("SELECT COUNT(*) as total FROM ordenes_servicio WHERE estado = 'entregado'")->fetch_assoc()['total'] ?? 0;
$clientes_total = $conn->query("SELECT COUNT(*) as total FROM clientes")->fetch_assoc()['total'] ?? 0;

$mes_actual = date('Y-m');
$ingresos_mes = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM facturas WHERE fecha_emision LIKE '$mes_actual%'")->fetch_assoc()['total'] ?? 0;
$bajo_stock = $conn->query("SELECT COUNT(*) as total FROM repuestos WHERE stock <= stock_minimo")->fetch_assoc()['total'] ?? 0;

// Órdenes pendientes por técnico
$tecnicos = $conn->query("
    SELECT u.nombre, COUNT(o.id) as total
    FROM usuarios u
    LEFT JOIN ordenes_servicio o ON u.id = o.tecnico_id AND o.estado NOT IN ('entregado', 'cancelado')
    WHERE u.rol = 'tecnico' AND u.estado = 'activo'
    GROUP BY u.id
    ORDER BY total DESC
    LIMIT 5
");

// Órdenes recientes
$ultimas = $conn->query("
    SELECT o.id, o.codigo, o.estado, o.prioridad, c.nombre as cliente, e.marca, e.modelo, o.fecha_ingreso
    FROM ordenes_servicio o
    LEFT JOIN clientes c ON o.cliente_id = c.id
    LEFT JOIN equipos e ON o.equipo_id = e.id
    ORDER BY o.fecha_ingreso DESC
    LIMIT 10
");

// Equipos próximos a vencer garantía
$garantias = $conn->query("
    SELECT g.id, g.orden_id, c.nombre as cliente, e.marca, e.modelo, g.fecha_fin
    FROM garantias g
    JOIN ordenes_servicio o ON g.orden_id = o.id
    LEFT JOIN clientes c ON o.cliente_id = c.id
    LEFT JOIN equipos e ON o.equipo_id = e.id
    WHERE g.fecha_fin >= CURDATE() AND g.fecha_fin <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY g.fecha_fin ASC
    LIMIT 5
");

// Alertas de inventario
$alertas_inventario = $conn->query("
    SELECT id, codigo, nombre, stock, stock_minimo
    FROM repuestos
    WHERE stock <= stock_minimo AND estado = 'activo'
    ORDER BY stock ASC
    LIMIT 5
");

// Gráfico de estados
$estados = $conn->query("SELECT estado, COUNT(*) as total FROM ordenes_servicio GROUP BY estado");
$estados_data = [];
while ($e = $estados->fetch_assoc()) {
    $estados_data[$e['estado']] = (int)$e['total'];
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
        <div class="text-muted">
            <i class="bi bi-calendar3"></i> <?= date('d \d\e F, Y') ?>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value"><?= $ordenes_total ?></div>
                        <div class="stat-label">Total Órdenes</div>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.5;">
                        <i class="bi bi-ticket-detailed"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning text-dark">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value"><?= $ordenes_abiertas ?></div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.5;">
                        <i class="bi bihourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value"><?= $ordenes_entregadas ?></div>
                        <div class="stat-label">Entregadas</div>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.5;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value"><?= formatMoney($ingresos_mes) ?></div>
                        <div class="stat-label">Ingresos Mes</div>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.5;">
                        <i class="bi bi-cash"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Órdenes Recientes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Equipo</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($o = $ultimas->fetch_assoc()): ?>
                                <tr onclick="window.location='<?= BASE_URL ?>ordenes/ver.php?id=<?= $o['id'] ?>'" style="cursor: pointer;">
                                    <td><strong><?= $o['codigo'] ?></strong></td>
                                    <td><?= htmlspecialchars($o['cliente']) ?></td>
                                    <td><?= htmlspecialchars($o['marca'] . ' ' . $o['modelo']) ?></td>
                                    <td><span class="badge" style="background-color: <?= COLORES_ESTADO[$o['estado']] ?? '#6c757d' ?>"><?= ESTADOS_ORDEN[$o['estado']] ?? $o['estado'] ?></span></td>
                                    <td><span class="badge bg-<?= $o['prioridad'] === 'urgente' ? 'danger' : ($o['prioridad'] === 'alta' ? 'warning' : 'secondary') ?>"><?= ucfirst($o['prioridad']) ?></span></td>
                                    <td><?= date('d/m H:i', strtotime($o['fecha_ingreso'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <?php if ($alertas_inventario->num_rows > 0): ?>
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning bg-opacity-10">
                    <h5 class="mb-0 text-warning"><i class="bi bi-exclamation-triangle"></i> Bajo Stock</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php while ($a = $alertas_inventario->fetch_assoc()): ?>
                        <a href="<?= BASE_URL ?>inventario/ver.php?id=<?= $a['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                            <div>
                                <strong><?= $a['codigo'] ?></strong>
                                <div class="text-muted small"><?= htmlspecialchars($a['nombre']) ?></div>
                            </div>
                            <span class="badge bg-danger"><?= $a['stock'] ?></span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Técnicos</h5>
                </div>
                <div class="card-body">
                    <?php while ($t = $tecnicos->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= htmlspecialchars($t['nombre']) ?></span>
                        <span class="badge bg-primary"><?= $t['total'] ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Deshabilitado auto-refresh por conflictos con extensiones Chrome
// document.addEventListener('DOMContentLoaded', function() {
//     setInterval(function() {
//         fetch('<?= BASE_URL ?>api/dashboard_stats.php?period=month')
//             .then(function(r) { return r.json(); })
//             .then(function(data) {
//                 console.log('Stats actualizadas:', data);
//             });
//     }, 30000);
// });
</script>
<?php require_once  '../include/footer.php'; ?>