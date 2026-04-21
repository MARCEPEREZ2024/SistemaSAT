<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/funciones.php';
require_once __DIR__ . '/../include/header.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$page_title = 'Reportes';

$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');
$tecnico_id = $_GET['tecnico'] ?? 0;

$tecnicos = getAllUsers();

$where_tecnico = $tecnico_id > 0 ? " AND tecnico_id = $tecnico_id" : "";

$ordenes_mes = $conn->query("
    SELECT estado, COUNT(*) as total 
    FROM ordenes_servicio 
    WHERE MONTH(fecha_ingreso) = $mes AND YEAR(fecha_ingreso) = $anio $where_tecnico
    GROUP BY estado
");

$ingresos = $conn->query("
    SELECT COALESCE(SUM(total), 0) as total 
    FROM facturas 
    WHERE MONTH(fecha_emision) = $mes AND YEAR(fecha_emision) = $anio AND estado_pago = 'pagado'
")->fetch_assoc()['total'];

$nuevas = $conn->query("
    SELECT COUNT(*) as total 
    FROM ordenes_servicio 
    WHERE MONTH(fecha_ingreso) = $mes AND YEAR(fecha_ingreso) = $anio $where_tecnico
")->fetch_assoc()['total'];

$reparadas = $conn->query("
    SELECT COUNT(*) as total 
    FROM ordenes_servicio 
    WHERE MONTH(fecha_ingreso) = $mes AND YEAR(fecha_ingreso) = $anio AND estado = 'reparado' $where_tecnico
")->fetch_assoc()['total'];

$clientes_nuevos = $conn->query("
    SELECT COUNT(*) as total 
    FROM clientes 
    WHERE MONTH(fecha_registro) = $mes AND YEAR(fecha_registro) = $anio
")->fetch_assoc()['total'];

$top_clientes = $conn->query("
    SELECT c.nombre, COUNT(o.id) as ordenes, SUM(o.costo_total) as total 
    FROM clientes c 
    JOIN ordenes_servicio o ON c.id = o.cliente_id 
    WHERE YEAR(o.fecha_ingreso) = $anio $where_tecnico
    GROUP BY c.id 
    ORDER BY total DESC 
    LIMIT 5
");

$top_tecnicos = $conn->query("
    SELECT u.nombre, COUNT(o.id) as ordenes, SUM(o.costo_total) as ingresos 
    FROM usuarios u 
    JOIN ordenes_servicio o ON u.id = o.tecnico_id 
    WHERE YEAR(o.fecha_ingreso) = $anio AND o.estado = 'reparado'
    GROUP BY u.id 
    ORDER BY ingresos DESC 
    LIMIT 5
");

$repuestos_mas = $conn->query("
    SELECT r.nombre, SUM(ro.cantidad) as cantidad, SUM(ro.cantidad * ro.precio_unitario) as total
    FROM repuestos_orden ro
    JOIN repuestos r ON ro.repuesto_id = r.id
    JOIN ordenes_servicio o ON ro.orden_id = o.id
    WHERE YEAR(o.fecha_ingreso) = $anio
    GROUP BY r.id
    ORDER BY total DESC
    LIMIT 5
");

$historial = $conn->query("
    SELECT MONTH(fecha_ingreso) as mes, COUNT(*) as total, SUM(costo_total) as ingresos
    FROM ordenes_servicio 
    WHERE fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY MONTH(fecha_ingreso) 
    ORDER BY mes
");

$estados_data = [];
while ($e = $ordenes_mes->fetch_assoc()) {
    $estados_data[] = $e;
}

$historial_data = [];
while ($h = $historial->fetch_assoc()) {
    $historial_data[] = $h;
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-bar-chart"></i> Reportes y Estadísticas</h1>
        <div>
            <a href="comisiones.php" class="btn btn-info">
                <i class="bi bi-cash"></i> Comisiones
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>
    
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-2">
                <select name="mes" class="form-select">
                    <?php for($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= $mes==$m?'selected':'' ?>><?= date('F', mktime(0,0,0,$m)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="anio" class="form-select">
                    <?php for($a=date('Y'); $a>=date('Y')-5; $a--): ?>
                    <option value="<?= $a ?>" <?= $anio==$a?'selected':'' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="tecnico" class="form-select">
                    <option value="0">Todos los técnicos</option>
                    <?php while($t = $tecnicos->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>" <?= $tecnico_id==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?= $nuevas ?></h3>
                    <p class="mb-0">Órdenes Nuevas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= $reparadas ?></h3>
                    <p class="mb-0">Reparadas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?= formatMoney($ingresos) ?></h3>
                    <p class="mb-0">Ingresos del Mes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= $clientes_nuevos ?></h3>
                    <p class="mb-0">Clientes Nuevos</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Órdenes por Estado</div>
                <div class="card-body">
                    <canvas id="chartEstados"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Historial de Órdenes (12 meses)</div>
                <div class="card-body">
                    <canvas id="chartHistorial"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">Top Clientes por Ingresos</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Cliente</th><th>Órdenes</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php $i=1; while($c = $top_clientes->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['nombre']) ?></td>
                                <td><?= $c['ordenes'] ?></td>
                                <td><?= formatMoney($c['total'] ?? 0) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">Top Técnicos</div>
                <div class="card-body">
                    <canvas id="chartTecnicos"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">Repuestos Más Usados</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Repuesto</th><th>Cant.</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php while($r = $repuestos_mas->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['nombre']) ?></td>
                                <td><?= $r['cantidad'] ?></td>
                                <td><?= formatMoney($r['total']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Resumen por Mes</div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Mes</th><th>Órdenes</th><th>Ingresos Estimados</th></tr></thead>
                <tbody>
                    <?php foreach($historial_data as $h): ?>
                    <tr>
                        <td><?= date('F', mktime(0,0,0,$h['mes'])) ?></td>
                        <td><?= $h['total'] ?></td>
                        <td><?= formatMoney($h['ingresos'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const estadosData = <?= json_encode($estados_data) ?>;
const historialData = <?= json_encode($historial_data) ?>;

new Chart(document.getElementById('chartEstados'), {
    type: 'doughnut',
    data: {
        labels: estadosData.map(e => e.estado),
        datasets: [{
            data: estadosData.map(e => e.total),
            backgroundColor: ['#6c757d', '#ffc107', '#fd7e14', '#0dcaf0', '#198754', '#dc3545']
        }]
    }
});

new Chart(document.getElementById('chartHistorial'), {
    type: 'line',
    data: {
        labels: historialData.map(h => {
            const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            return meses[h.mes - 1];
        }),
        datasets: [{
            label: 'Órdenes',
            data: historialData.map(h => h.total),
            borderColor: '#0d6efd',
            tension: 0.3
        }]
    },
    options: { responsive: true }
});

new Chart(document.getElementById('chartTecnicos'), {
    type: 'bar',
    data: {
        labels: ['Técnico 1', 'Técnico 2', 'Técnico 3'],
        datasets: [{
            label: 'Ingresos',
            data: [1500, 1200, 900],
            backgroundColor: '#198754'
        }]
    },
    options: { indexAxis: 'y', responsive: true }
});
</script>
<?php require_once __DIR__ . '/../include/footer.php'; ?>