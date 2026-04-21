<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Cotizaciones';
$estado = $_GET['estado'] ?? '';

$sql = "SELECT c.*, cl.nombre as cliente_nombre, cl.telefono 
        FROM cotizaciones c 
        JOIN clientes cl ON c.cliente_id = cl.id 
        WHERE 1=1";

if ($estado) $sql .= " AND c.estado = '$estado'";
$sql .= " ORDER BY c.fecha_creacion DESC";

$cotizaciones = $conn->query($sql);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-ruled"></i> Cotizaciones</h1>
        <a href="agregar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Cotización
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="aprobado" <?= $estado === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="rechazado" <?= $estado === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                            <option value="convertido" <?= $estado === 'convertido' ? 'selected' : '' ?>>Convertido</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Validez</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = $cotizaciones->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $c['numero_cotizacion'] ?></strong></td>
                            <td><?= htmlspecialchars($c['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars(mb_substr($c['descripcion'] ?? '', 0, 50)) ?>...</td>
                            <td>
                                <span class="badge bg-<?= match($c['estado']) { 'pendiente'=>'warning','aprobado'=>'success','rechazado'=>'danger','convertido'=>'info' } ?>">
                                    <?= ucfirst($c['estado']) ?>
                                </span>
                            </td>
                            <td><?= $c['validez_dias'] ?> días</td>
                            <td><?= date('d/m/Y', strtotime($c['fecha_creacion'])) ?></td>
                            <td>
                                <a href="ver.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <?php if ($c['estado'] === 'pendiente'): ?>
                                <a href="ver.php?id=<?= $c['id'] ?>&action=aprobar" class="btn btn-sm btn-outline-success" title="Aprobar"><i class="bi bi-check"></i></a>
                                <?php endif; ?>
                                <?php if ($c['estado'] === 'aprobado'): ?>
                                <a href="ver.php?id=<?= $c['id'] ?>&action=convertir" class="btn btn-sm btn-outline-info" title="Convertir a Presupuesto"><i class="bi bi-arrow-right"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>