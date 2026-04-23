<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/sucursal_helper.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

if (!isAdmin()) {
    redirect('../dashboard/index.php');
}

$page_title = 'Sucursales';

$conn = getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'crear') {
            $data = [
                'nombre' => sanitize($_POST['nombre']),
                'direccion' => sanitize($_POST['direccion']),
                'telefono' => sanitize($_POST['telefono']),
                'email' => sanitize($_POST['email']),
                'responsable_id' => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
                'estado' => 'activo'
            ];
            
            if (crearSucursal($conn, $data)) {
                $success = 'Sucursal creada correctamente';
            } else {
                $error = 'Error al crear la sucursal';
            }
        } elseif ($_POST['action'] === 'editar') {
            $id = (int)$_POST['id'];
            $data = [
                'nombre' => sanitize($_POST['nombre']),
                'direccion' => sanitize($_POST['direccion']),
                'telefono' => sanitize($_POST['telefono']),
                'email' => sanitize($_POST['email']),
                'responsable_id' => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
                'estado' => sanitize($_POST['estado'])
            ];
            
            if (actualizarSucursal($conn, $id, $data)) {
                $success = 'Sucursal actualizada correctamente';
            } else {
                $error = 'Error al actualizar';
            }
        } elseif ($_POST['action'] === 'eliminar') {
            $id = (int)$_POST['id'];
            if (eliminarSucursal($conn, $id)) {
                $success = 'Sucursal eliminada correctamente';
            } else {
                $error = 'No se puede eliminar la sucursal principal';
            }
        }
    }
}

$sucursales = getSucursales($conn);

$responsables = $conn->query("SELECT id, nombre FROM usuarios WHERE estado = 'activo' ORDER BY nombre");

require_once '../include/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-building"></i> Sucursales</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSucursal">
            <i class="bi bi-plus-lg"></i> Nueva Sucursal
        </button>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <?php while ($s = $sucursales->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= htmlspecialchars($s['nombre']) ?></h5>
                    <span class="badge bg-<?= $s['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                        <?= $s['estado'] ?>
                    </span>
                </div>
                <div class="card-body">
                    <p class="mb-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($s['direccion'] ?? 'Sin dirección') ?></p>
                    <p class="mb-1"><i class="bi bi-telephone"></i> <?= htmlspecialchars($s['telefono'] ?? 'Sin teléfono') ?></p>
                    <p class="mb-0"><i class="bi bi-envelope"></i> <?= htmlspecialchars($s['email'] ?? 'Sin email') ?></p>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-sm btn-primary" onclick="editarSucursal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nombre']) ?>', '<?= htmlspecialchars($s['direccion'] ?? '') ?>', '<?= htmlspecialchars($s['telefono'] ?? '') ?>', '<?= htmlspecialchars($s['email'] ?? '') ?>', '<?= $s['estado'] ?>')">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                    <?php if ($s['id'] != 1): ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar sucursal?');">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="modalSucursal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Sucursal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="crear">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Responsable</label>
                        <select name="responsable_id" class="form-select">
                            <option value="">Sin asignar</option>
                            <?php while ($r = $responsables->fetch_assoc()): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Sucursal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="editar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="edit_direccion" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" id="edit_telefono" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" id="edit_estado" class="form-select">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarSucursal(id, nombre, direccion, telefono, email, estado) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_direccion').value = direccion;
    document.getElementById('edit_telefono').value = telefono;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_estado').value = estado;
    
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
<?php require_once '../include/footer.php'; ?>