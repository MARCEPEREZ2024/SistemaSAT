<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Presupuestos';
$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? '';

$sql = "SELECT p.*, c.nombre as cliente_nombre, c.telefono, c.dni, o.codigo as orden_codigo 
        FROM presupuestos p 
        LEFT JOIN clientes c ON p.cliente_id = c.id 
        LEFT JOIN ordenes_servicio o ON p.orden_id = o.id 
        WHERE 1=1";

if ($search) {
    $search_esc = $conn->real_escape_string($search);
    $sql .= " AND (p.numero_presupuesto LIKE '%$search_esc%' OR c.nombre LIKE '%$search_esc%' OR c.dni LIKE '%$search_esc%')";
}

if ($estado) {
    $sql .= " AND p.estado = '$estado'";
}

$sql .= " ORDER BY p.fecha_creacion DESC";

$presupuestos = $conn->query($sql);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-text"></i> Presupuestos</h1>
        <a href="agregar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Presupuesto
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por número, cliente o DNI" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="aprobado" <?= $estado === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="rechazado" <?= $estado === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                            <option value="convertido" <?= $estado === 'convertido' ? 'selected' : '' ?>>Convertido a Factura</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Orden</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Validez</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $presupuestos->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $p['numero_presupuesto'] ?></strong></td>
                            <td><?= htmlspecialchars($p['cliente_nombre']) ?></td>
                            <td><?= $p['orden_codigo'] ?? '-' ?></td>
                            <td><?= formatMoney($p['total']) ?></td>
                            <td>
                                <?php 
                                $badgeClass = match($p['estado']) {
                                    'pendiente' => 'warning',
                                    'aprobado' => 'success',
                                    'rechazado' => 'danger',
                                    'convertido' => 'info',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($p['estado']) ?></span>
                            </td>
                            <td><?= $p['validez_dias'] ?> días</td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_creacion'])) ?></td>
                            <td>
                                <a href="ver.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($p['estado'] === 'pendiente'): ?>
                                <a href="ver.php?id=<?= $p['id'] ?>&action=aprobar" class="btn btn-sm btn-outline-success" title="Aprobar">
                                    <i class="bi bi-check-circle"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($p['estado'] === 'aprobado'): ?>
                                <a href="ver.php?id=<?= $p['id'] ?>&action=convertir" class="btn btn-sm btn-outline-info" title="Convertir a Factura">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($presupuestos->num_rows == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No se encontraron presupuestos</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>