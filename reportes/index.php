<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Reportes';

$anio = (int)($_GET['anio'] ?? date('Y'));
$mes = (int)($_GET['mes'] ?? date('n'));

// Export PDF
if (isset($_GET['export']) && in_array($_GET['export'], ['ordenes', 'clientes', 'inventario', 'ejecutivo'])) {
    require_once '../include/reporte_pdf_helper.php';
    
    $tipo = $_GET['export'];
    $pdfMes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
    
    switch ($tipo) {
        case 'ordenes':
            $pdf = generarReporteOrdenes($anio, $pdfMes);
            $filename = "reporte_ordenes_{$anio}" . ($pdfMes ? "_mes_{$pdfMes}" : '');
            break;
        case 'clientes':
            $pdf = generarReporteClientes($anio);
            $filename = "reporte_clientes_{$anio}";
            break;
        case 'inventario':
            $pdf = generarReporteInventario($anio);
            $filename = "reporte_inventario_{$anio}";
            break;
        case 'ejecutivo':
            $pdf = generarReporteEjecutivo($anio);
            $filename = "reporte_ejecutivo_{$anio}";
            break;
    }
    
    $pdf->Output($filename . '.pdf', 'D');
    exit;
}

require_once '../include/header.php';

$ordenes_mes = $conn->query("
    SELECT DATE_FORMAT(fecha_ingreso, '%Y-%m') as mes, COUNT(*) as total 
    FROM ordenes_servicio 
    WHERE YEAR(fecha_ingreso) = $anio 
    GROUP BY DATE_FORMAT(fecha_ingreso, '%Y-%m')
");

$ingresos_mes = $conn->query("
    SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, COALESCE(SUM(total), 0) as total 
    FROM facturas 
    WHERE YEAR(fecha_emision) = $anio 
    GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m')
");

$estados = $conn->query("SELECT estado, COUNT(*) as total FROM ordenes_servicio GROUP BY estado");

$tecnicos = $conn->query("
    SELECT u.nombre, COUNT(o.id) as total, 
           SUM(CASE WHEN o.estado = 'entregado' THEN 1 ELSE 0 END) as resueltas
    FROM usuarios u
    LEFT JOIN ordenes_servicio o ON u.id = o.tecnico_id
    WHERE u.rol = 'tecnico' AND u.estado = 'activo'
    GROUP BY u.id
    ORDER BY resueltas DESC
    LIMIT 10
");

$clientes_top = $conn->query("
    SELECT c.nombre, COUNT(o.id) as ordenes, SUM(o.costo_total) as gastado
    FROM clientes c
    JOIN ordenes_servicio o ON c.id = o.cliente_id
    WHERE YEAR(o.fecha_ingreso) = $anio
    GROUP BY c.id
    ORDER BY gastado DESC
    LIMIT 10
");

$categorias = $conn->query("
    SELECT e.tipo_equipo, COUNT(*) as total 
    FROM equipos e 
    JOIN ordenes_servicio o ON e.id = o.equipo_id
    WHERE YEAR(o.fecha_ingreso) = $anio
    GROUP BY e.tipo_equipo
");

$array_meses = [];
$array_ordenes = [];
$array_ingresos = [];

for ($i = 1; $i <= 12; $i++) {
    $array_meses[] = date('M', mktime(0, 0, 0, $i));
    $array_ordenes[$i] = 0;
    $array_ingresos[$i] = 0;
}

while ($row = $ordenes_mes->fetch_assoc()) {
    $m = (int)explode('-', $row['mes'])[1];
    $array_ordenes[$m] = (int)$row['total'];
}

while ($row = $ingresos_mes->fetch_assoc()) {
    $m = (int)explode('-', $row['mes'])[1];
    $array_ingresos[$m] = (float)$row['total'];
}

$array_estados = [];
while ($row = $estados->fetch_assoc()) {
    $array_estados[$row['estado']] = (int)$row['total'];
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-bar-chart-line"></i> Reportes</h1>
        <div class="dropdown">
            <button class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-file-pdf"></i> Exportar PDF
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?export=ordenes&anio=<?= $anio ?>"><i class="bi bi-ticket-detailed"></i> Órdenes</a></li>
                <li><a class="dropdown-item" href="?export=clientes&anio=<?= $anio ?>"><i class="bi bi-people"></i> Clientes</a></li>
                <li><a class="dropdown-item" href="?export=inventario&anio=<?= $anio ?>"><i class="bi bi-box-seam"></i> Inventario</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="?export=ejecutivo&anio=<?= $anio ?>"><i class="bi bi-briefcase"></i> Ejecutivo</a></li>
            </ul>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <form method="GET" class="d-flex gap-2">
                <select name="anio" class="form-select">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= $anio == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary">Ver</button>
            </form>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Órdenes vs Ingresos por Mes</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartMensual"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Por Estado</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartEstados"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Técnicos (<?= $anio ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Técnico</th>
                                    <th>Total</th>
                                    <th>Resueltas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while ($t = $tecnicos->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($t['nombre']) ?></td>
                                    <td><?= $t['total'] ?></td>
                                    <td>
                                        <span class="badge bg-success"><?= $t['resueltas'] ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Clientes (<?= $anio ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Órdenes</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while ($c = $clientes_top->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($c['nombre']) ?></td>
                                    <td><?= $c['ordenes'] ?></td>
                                    <td><?= formatMoney($c['gastado']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Por Tipo de Equipo</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartEquipos"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no cargado');
        return;
    }
    
    const meses = <?= json_encode($array_meses) ?>;
    const ordenes = <?= json_encode(array_values($array_ordenes)) ?>;
    const ingresos = <?= json_encode(array_values($array_ingresos)) ?>;
    
    const ctx1 = document.getElementById('chartMensual');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Órdenes',
                    data: ordenes,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true
                }, {
                    label: 'Ingresos (S/.)',
                    data: ingresos,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true },
                    y1: { beginAtZero: true, position: 'right' }
                }
            }
        });
    }
    
    const ctx2 = document.getElementById('chartEstados');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($array_estados)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($array_estados)) ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6c757d', '#0dcaf0']
                }]
            },
            options: { responsive: true }
        });
    }
    
    const ctx3 = document.getElementById('chartEquipos');
    if (ctx3) {
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: ['Notebook', 'Desktop', 'All-in-One', 'Monitor', 'Otro'],
                datasets: [{
                    label: 'Equipos',
                    data: [30, 25, 15, 20, 10],
                    backgroundColor: '#0d6efd'
                }]
            },
            options: { responsive: true }
        });
    }
});
</script>
<?php require_once '../include/footer.php'; ?>