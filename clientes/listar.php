<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Clientes';
$search = $_GET['search'] ?? '';
$clientes = getAllClientes($search);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-people"></i> Clientes</h1>
        <a href="agregar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Cliente
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre, email, teléfono o DNI..." value="<?= htmlspecialchars($search) ?>">
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
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>DNI</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                        <tr>
                            <td><?= $cliente['id'] ?></td>
                            <td><?= htmlspecialchars($cliente['nombre']) ?></td>
                            <td><?= htmlspecialchars($cliente['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                            <td><?= htmlspecialchars($cliente['dni'] ?? '-') ?></td>
                            <td><?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?></td>
                            <td>
                                <a href="ver.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editar.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($clientes->num_rows == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No se encontraron clientes</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>