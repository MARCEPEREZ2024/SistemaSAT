<?php
require_once  '../config/database.php';
require_once  '../config/config.php';
require_once  '../include/funciones.php';
require_once  '../include/filters_helper.php';
require_once  '../include/pagination_helper.php';
require_once  '../include/export_helper.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Órdenes de Servicio';
$get = $_GET;
$filtros = aplicar_filtros_ordenes($get);

$page = max(1, (int)($get['page'] ?? 1));
$per_page = 15;

$conn = getConnection();

// Agregar búsqueda al filtro si existe
if (!empty($get['search'])) {
    $s = "%" . $conn->real_escape_string($get['search']) . "%";
    $filtros .= " AND (o.codigo LIKE '$s' OR c.nombre LIKE '$s' OR e.marca LIKE '$s' OR e.modelo LIKE '$s')";
}

// Contar total
$sql_total = "SELECT COUNT(*) as total FROM ordenes_servicio o 
            LEFT JOIN clientes c ON o.cliente_id = c.id 
            LEFT JOIN equipos e ON o.equipo_id = e.id 
            WHERE 1=1$filtros";
$result_total = $conn->query($sql_total);
$total = $result_total ? $result_total->fetch_assoc()['total'] : 0;

$pagination = paginate($total, $per_page, $page);

// Obtener órdenes filtradas
$sql = "SELECT o.*, c.nombre as cliente_nombre, e.marca, e.modelo, u.nombre as tecnico_nombre 
        FROM ordenes_servicio o 
        LEFT JOIN clientes c ON o.cliente_id = c.id 
        LEFT JOIN equipos e ON o.equipo_id = e.id 
        LEFT JOIN usuarios u ON o.tecnico_id = u.id 
        WHERE 1=1 $filtros 
        ORDER BY o.fecha_ingreso DESC 
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";

$ordenes = $conn->query($sql);

// Exportar ANTES de cualquier output HTML
if (isset($get['export'])) {
    $data = [];
    while ($o = $ordenes->fetch_assoc()) {
        $data[] = [
            $o['codigo'],
            $o['cliente_nombre'],
            $o['marca'] . ' ' . $o['modelo'],
            $o['estado'],
            $o['prioridad'],
            $o['tecnico_nombre'] ?? 'Sin asignar',
            $o['fecha_ingreso'],
            $o['costo_total']
        ];
    }
    export_to_csv($data, 'ordenes_filtro', ['Código', 'Cliente', 'Equipo', 'Estado', 'Prioridad', 'Técnico', 'Fecha', 'Costo']);
}

require_once  '../include/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-ticket-detailed"></i> Órdenes (<?= $total ?>)</h1>
        <div>
            <a href="?<?= generar_url_filtros($get, ['export' => 'csv']) ?>" class="btn btn-success">
                <i class="bi bi-download"></i> Exportar
            </a>
            <a href="agregar.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> Nueva
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
                <a href="?" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (ESTADOS_ORDEN as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($get['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prioridad</label>
                    <select name="prioridad" class="form-select">
                        <option value="">Todas</option>
                        <option value="baja" <?= ($get['prioridad'] ?? '') === 'baja' ? 'selected' : '' ?>>Baja</option>
                        <option value="normal" <?= ($get['prioridad'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="alta" <?= ($get['prioridad'] ?? '') === 'alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="urgente" <?= ($get['prioridad'] ?? '') === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Técnico</label>
                    <select name="tecnico_id" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $tecnicos = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'tecnico' AND estado = 'activo'");
                        while ($t = $tecnicos->fetch_assoc()): ?>
                        <option value="<?= $t['id'] ?>" <?= ($get['tecnico_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= $get['fecha_inicio'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= $get['fecha_fin'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-control" placeholder="Código, cliente..." value="<?= htmlspecialchars($get['search'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
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
                            <th>Técnico</th>
                            <th>Fecha</th>
                            <th>Costo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($o = $ordenes->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $o['codigo'] ?></strong></td>
                            <td><?= htmlspecialchars($o['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($o['marca'] . ' ' . $o['modelo']) ?></td>
                            <td><span class="badge" style="background-color: <?= COLORES_ESTADO[$o['estado']] ?? '#6c757d' ?>"><?= ESTADOS_ORDEN[$o['estado']] ?? $o['estado'] ?></span></td>
                            <td><span class="badge bg-<?= $o['prioridad'] === 'urgente' ? 'danger' : ($o['prioridad'] === 'alta' ? 'warning' : 'secondary') ?>"><?= ucfirst($o['prioridad']) ?></span></td>
                            <td><?= htmlspecialchars($o['tecnico_nombre'] ?? '-') ?></td>
                            <td><?= date('d/m/Y', strtotime($o['fecha_ingreso'])) ?></td>
                            <td><?= formatMoney($o['costo_total']) ?></td>
                            <td>
                                <a href="ver.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($ordenes->num_rows == 0): ?>
                        <tr><td colspan="9" class="text-center text-muted p-4">Sin resultados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total > $per_page): ?>
            <div class="card-footer">
                <?= pagination_links('listar.php', $pagination) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once  '../include/footer.php'; ?>