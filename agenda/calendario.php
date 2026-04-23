<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/include/funciones.php';
require_once __DIR__ . '/include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Calendario';
$mes = (int)($_GET['mes'] ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

$mes = max(1, min(12, $mes));
$anio = max(2020, min(2030, $anio));

$mes_anterior = $mes == 1 ? 12 : $mes - 1;
$anio_anterior = $mes == 1 ? $anio - 1 : $anio;
$mes_siguiente = $mes == 12 ? 1 : $mes + 1;
$anio_siguiente = $mes == 12 ? $anio + 1 : $anio;

$conn = getConnection();

$fecha_inicio = "$anio-$mes-01";
$fecha_fin = date('Y-m-t', strtotime($fecha_inicio));

$entregas = $conn->query("
    SELECT e.id, e.orden_id, e.fecha_entrega, e.hora_entrega, e.nota, 
           o.codigo, c.nombre as cliente_nombre, e.marca, e.modelo
    FROM agenda_entregas e
    LEFT JOIN ordenes_servicio o ON e.orden_id = o.id
    LEFT JOIN clientes c ON o.cliente_id = c.id
    LEFT JOIN equipos eq ON o.equipo_id = eq.id
    WHERE e.fecha_entrega BETWEEN '$fecha_inicio' AND '$fecha_fin'
    ORDER BY e.fecha_entrega, e.hora_entrega
");

$eventos = [];
while ($e = $entregas->fetch_assoc()) {
    $dia = (int)date('j', strtotime($e['fecha_entrega']));
    $eventos[$dia][] = $e;
}

$primer_dia = mktime(0, 0, 0, $mes, 1, $anio);
$dias_mes = date('t', $primer_dia);
$dia_semana = date('N', $primer_dia);

$nombre_mes = date('F', $primer_dia);
$dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-calendar3"></i> Calendario de Entregas</h1>
        <a href="agregar.php" class="btn btn-primary">
            <i class="bi bi-plus"></i> Nueva Entrega
        </a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <a href="?mes=<?= $mes_anterior ?>&anio=<?= $anio_anterior ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <h4 class="mb-0"><?= $nombre_mes ?> <?= $anio ?></h4>
                <a href="?mes=<?= $mes_siguiente ?>&anio=<?= $anio_siguiente ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0" style="table-layout: fixed;">
                <thead>
                    <tr>
                        <?php foreach ($dias_semana as $d): ?>
                        <th class="text-center"><?= $d ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $semana = 0;
                    $dia_actual = 1;
                    while ($dia_actual <= $dias_mes):
                    ?>
                    <tr>
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                        <?php if (($semana == 0 && $i < $dia_semana) || $dia_actual > $dias_mes): ?>
                        <td class="bg-light"></td>
                        <?php else: ?>
                        <td valign="top" class="<?= date('Y-m-d') == "$anio-$mes-$dia_actual" ? 'bg-info bg-opacity-10' : '' ?>" style="min-height: 80px;">
                            <div class="d-flex justify-content-between">
                                <strong><?= $dia_actual ?></strong>
                                <?php if (isset($eventos[$dia_actual])): ?>
                                <span class="badge bg-primary"><?= count($eventos[$dia_actual]) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($eventos[$dia_actual])): ?>
                            <?php foreach ($eventos[$dia_actual] as $ev): ?>
                            <a href="<?= BASE_URL ?>ordenes/ver.php?id=<?= $ev['orden_id'] ?>" class="d-block text-decoration-none">
                                <div class="small p-1 mb-1 bg-<?= $ev['hora_entrega'] ? 'warning' : 'success' ?>-subtle rounded">
                                    <strong><?= $ev['codigo'] ?></strong><br>
                                    <?= htmlspecialchars($ev['cliente_nombre']) ?>
                                    <?php if ($ev['hora_entrega']): ?>
                                    <span class="text-muted"><?= $ev['hora_entrega'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <?php $dia_actual++; endif; ?>
                        <?php endfor; ?>
                    </tr>
                    <?php $semana++; endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Próximas Entregas</h5>
                </div>
                <div class="card-body">
                    <?php
                    $proximas = $conn->query("
                        SELECT e.*, o.codigo, c.nombre as cliente
                        FROM agenda_entregas e
                        JOIN ordenes_servicio o ON e.orden_id = o.id
                        JOIN clientes c ON o.cliente_id = c.id
                        WHERE e.fecha_entrega >= CURDATE()
                        ORDER BY e.fecha_entrega ASC
                        LIMIT 5
                    ");
                    ?>
                    <div class="list-group">
                        <?php while ($p = $proximas->fetch_assoc()): ?>
                        <a href="<?= BASE_URL ?>ordenes/ver.php?id=<?= $p['orden_id'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?= $p['codigo'] ?></strong>
                                    <div class="text-muted"><?= htmlspecialchars($p['cliente']) ?></div>
                                </div>
                                <div class="text-end">
                                    <?= date('d/m', strtotime($p['fecha_entrega'])) ?>
                                    <?php if ($p['hora_entrega']): ?>
                                    <div class="small text-muted"><?= $p['hora_entrega'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Leyenda</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div><span class="badge bg-success-subtle p-2">Con hora</span></div>
                        <div><span class="badge bg-warning-subtle p-2">Sin hora</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/include/footer.php'; ?>