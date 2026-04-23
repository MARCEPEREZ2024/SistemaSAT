<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Agenda de Entregas';

$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

$inicio = "$anio-$mes-01";
$fin = date("Y-m-t", strtotime($inicio));

$entregas = $conn->query("
    SELECT a.*, o.codigo, c.nombre as cliente_nombre, e.marca, e.modelo 
    FROM agenda_entregas a
    JOIN ordenes_servicio o ON a.orden_id = o.id
    JOIN clientes c ON o.cliente_id = c.id
    LEFT JOIN equipos e ON o.equipo_id = e.id
    WHERE a.fecha_entrega BETWEEN '$inicio' AND '$fin'
    ORDER BY a.fecha_entrega, a.hora_entrega
");

$entregas_array = [];
while ($e = $entregas->fetch_assoc()) {
    $entregas_array[$e['fecha_entrega']][] = $e;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $orden_id = (int)$_POST['orden_id'];
    $fecha = $_POST['fecha_entrega'];
    $hora = $_POST['hora_entrega'];
    $nota = sanitize($_POST['nota']);
    
    $stmt = $conn->prepare("INSERT INTO agenda_entregas (orden_id, fecha_entrega, hora_entrega, nota) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $orden_id, $fecha, $hora, $nota);
    
    if ($stmt->execute()) {
        redirect('index.php?mes=' . $mes . '&anio=' . $anio);
    }
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $conn->query("DELETE FROM agenda_entregas WHERE id = $id");
    redirect('index.php?mes=' . $mes . '&anio=' . $anio);
}

$ordenes_pendientes = $conn->query("
    SELECT o.id, o.codigo, c.nombre as cliente_nombre, e.marca, e.modelo 
    FROM ordenes_servicio o
    JOIN clientes c ON o.cliente_id = c.id
    LEFT JOIN equipos e ON o.equipo_id = e.id
    WHERE o.estado = 'reparado' OR o.estado_orden = 'cerrada'
    ORDER BY o.codigo DESC
");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-calendar-check"></i> Agenda de Entregas</h1>
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
                    <?php for($a=date('Y'); $a>=date('Y')-1; $a--): ?>
                    <option value="<?= $a ?>" <?= $anio==$a?'selected':'' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Ver</button>
            </div>
        </div>
    </form>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Nueva Entrega</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="orden_id" class="form-label">Orden</label>
                            <select id="orden_id" name="orden_id" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php while ($o = $ordenes_pendientes->fetch_assoc()): ?>
                                <option value="<?= $o['id'] ?>"><?= $o['codigo'] ?> - <?= htmlspecialchars($o['cliente_nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_entrega" class="form-label">Fecha</label>
                            <input type="date" id="fecha_entrega" name="fecha_entrega" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="hora_entrega" class="form-label">Hora</label>
                            <input type="time" id="hora_entrega" name="hora_entrega" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="nota" class="form-label">Nota</label>
                            <textarea id="nota" name="nota" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" name="agregar" class="btn btn-success w-100">Programar Entrega</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Calendario - <?= date('F', mktime(0,0,0,$mes)) ?> <?= $anio ?></h5>
                </div>
                <div class="card-body">
                    <?php 
                    $dias_semana = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
                    $primer_dia = date('w', strtotime($inicio));
                    $dias_mes = date('t', strtotime($inicio));
                    ?>
                    <table class="table table-bordered calendar-table">
                        <thead>
                            <tr>
                                <?php foreach($dias_semana as $d): ?><th class="text-center"><?= $d ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <?php 
                            for ($i = 0; $i < $primer_dia; $i++) echo '<td></td>';
                            for ($dia = 1; $dia <= $dias_mes; $dia++):
                                $fecha = sprintf('%s-%02d-%02d', $anio, $mes, $dia);
                                $tiene_entregas = isset($entregas_array[$fecha]);
                            ?>
                                <td class="<?= $tiene_entregas ? 'bg-success text-white' : '' ?>" style="vertical-align: top; min-height: 80px;">
                                    <strong><?= $dia ?></strong>
                                    <?php if ($tiene_entregas): ?>
                                        <?php foreach($entregas_array[$fecha] as $e): ?>
                                        <div class="small mt-1">
                                            <a href="../ordenes/ver.php?id=<?= $e['orden_id'] ?>" class="text-white text-decoration-none">
                                                <?= $e['codigo'] ?><br>
                                                <?= htmlspecialchars($e['cliente_nombre']) ?>
                                            </a>
                                            <a href="?mes=<?= $mes ?>&anio=<?= $anio ?>&eliminar=<?= $e['id'] ?>" class="btn btn-sm btn-light btn-sm" onclick="return confirm('¿Eliminar?')">×</a>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            <?php if (($dia + $primer_dia) % 7 == 0) echo '</tr><tr>'; endfor; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-table td { height: 80px; }
</style>
<?php require_once '../include/footer.php'; ?>