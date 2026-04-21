<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

if (!isAdmin()) {
    redirect('dashboard/index.php');
}

$page_title = 'Comisiones por Técnico';

$anio = $_GET['anio'] ?? date('Y');
$mes = $_GET['mes'] ?? 0;

$tecnicos = getAllUsers();

$sql = "
    SELECT u.id as tecnico_id, u.nombre as tecnico_nombre,
           u.comision_venta, u.comision_presentismo, u.comision_especial,
           COUNT(o.id) as ordenes_completadas,
           COALESCE(SUM(o.costo_reparacion), 0) as total_reparaciones
    FROM usuarios u
    LEFT JOIN ordenes_servicio o ON u.id = o.tecnico_id 
        AND o.estado = 'reparado' 
        AND YEAR(o.fecha_reparacion) = $anio
";

if ($mes > 0) {
    $sql .= " AND MONTH(o.fecha_reparacion) = $mes";
}

$sql .= " GROUP BY u.id ORDER BY u.nombre";

$comisiones = $conn->query($sql);

$comisiones_array = [];
$total_venta = 0;
$total_presentismo = 0;
$total_especial = 0;

while ($c = $comisiones->fetch_assoc()) {
    $c_venta = (float)($c['comision_venta'] ?? 10);
    $c_presentismo = (float)($c['comision_presentismo'] ?? 5);
    $c_especial = (float)($c['comision_especial'] ?? 15);
    
    $c['comision_venta'] = $c_venta;
    $c['comision_presentismo'] = $c_presentismo;
    $c['comision_especial'] = $c_especial;
    
    $comisiones_array[] = $c;
    $total_venta += $c['total_reparaciones'] * ($c_venta / 100);
    $total_presentismo += $c['ordenes_completadas'] * $c_presentismo;
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-cash"></i> Comisiones por Técnico</h1>
    </div>
    
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-2">
                <select name="mes" class="form-select">
                    <option value="0">Todo el año</option>
                    <?php for($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= $mes==$m?'selected':'' ?>><?= date('F', mktime(0,0,0,$m)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="anio" class="form-select">
                    <?php for($a=date('Y'); $a>=date('Y')-3; $a--): ?>
                    <option value="<?= $a ?>" <?= $anio==$a?'selected':'' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= formatMoney($total_venta) ?></h3>
                    <p class="mb-0">Total Comisiones por Venta</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?= formatMoney($total_presentismo) ?></h3>
                    <p class="mb-0">Total Presentismo</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= formatMoney($total_venta + $total_presentismo) ?></h3>
                    <p class="mb-0">Total General</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Detalle por Técnico</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Técnico</th>
                        <th class="text-center">Órdenes</th>
                        <th class="text-end">Reparaciones</th>
                        <th class="text-center">% Venta</th>
                        <th class="text-end">Com. Venta</th>
                        <th class="text-center">% Pres.</th>
                        <th class="text-end">Presentismo</th>
                        <th class="text-end"><strong>Total</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comisiones_array as $c): ?>
                    <?php if ($c['ordenes_completadas'] > 0 || true): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['tecnico_nombre']) ?></strong></td>
                        <td class="text-center">
                            <span class="badge bg-primary"><?= $c['ordenes_completadas'] ?></span>
                        </td>
                        <td class="text-end"><?= formatMoney($c['total_reparaciones']) ?></td>
                        <td class="text-center"><?= $c['comision_venta'] ?>%</td>
                        <td class="text-end text-success"><?= formatMoney($c['total_reparaciones'] * $c['comision_venta'] / 100) ?></td>
                        <td class="text-center"><?= $c['comision_presentismo'] ?>%</td>
                        <td class="text-end text-info"><?= formatMoney($c['ordenes_completadas'] * $c['comision_presentismo']) ?></td>
                        <td class="text-end">
                            <strong><?= formatMoney(($c['total_reparaciones'] * $c['comision_venta'] / 100) + ($c['ordenes_completadas'] * $c['comision_presentismo'])) ?></strong>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-success">
                        <td><strong>Total</strong></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-end"><strong><?= formatMoney($total_venta) ?></strong></td>
                        <td></td>
                        <td class="text-end"><strong><?= formatMoney($total_presentismo) ?></strong></td>
                        <td class="text-end"><strong><?= formatMoney($total_venta + $total_presentismo) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Configuración de Comisiones</h5>
        </div>
        <div class="card-body">
            <p>Las comisiones se configuran por cada usuario en la sección de <strong>Gestión de Usuarios</strong>.</p>
            <ul>
                <li><strong>Comisión por Venta:</strong> Porcentaje sobre el total de reparaciones completadas</li>
                <li><strong>Presentismo:</strong> Monto fijo por cada orden completada</li>
                <li><strong>Comisión Especial:</strong> Reservada para trabajos especiales (no calculada automáticamente)</li>
            </ul>
            <a href="<?= BASE_URL ?>usuarios/listar.php" class="btn btn-primary">
                <i class="bi bi-gear"></i> Configurar Comisiones
            </a>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>