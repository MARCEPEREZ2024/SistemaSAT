<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Equipos';
$search = $_GET['search'] ?? '';
$equipos = getAllEquipos($search);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-laptop"></i> Equipos</h1>
        <a href="agregar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Equipo
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por marca, modelo, serie o cliente..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Tipo</th>
                            <th>Serie</th>
                            <th>Estado</th>
                            <th>Fecha Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($equipo = $equipos->fetch_assoc()): ?>
                        <tr>
                            <td><?= $equipo['id'] ?></td>
                            <td><?= htmlspecialchars($equipo['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($equipo['marca']) ?></td>
                            <td><?= htmlspecialchars($equipo['modelo'] ?? '-') ?></td>
                            <td><?= ucfirst($equipo['tipo_equipo']) ?></td>
                            <td><?= htmlspecialchars($equipo['serie'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $equipo['estado_equipo'] === 'bueno' ? 'success' : ($equipo['estado_equipo'] === 'regular' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($equipo['estado_equipo']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($equipo['fecha_ingreso'])) ?></td>
                            <td>
                                <a href="ver.php?id=<?= $equipo['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editar.php?id=<?= $equipo['id'] ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($equipos->num_rows == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No se encontraron equipos</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>