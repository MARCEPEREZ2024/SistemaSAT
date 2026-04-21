<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

if (!isAdmin()) {
    redirect('../dashboard/index.php');
}

$page_title = 'Usuarios';
$editando = $_GET['editar'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $nombre = sanitize($_POST['nombre']);
    $email = sanitize($_POST['email']);
    $telefono = sanitize($_POST['telefono']);
    $rol = sanitize($_POST['rol']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, telefono, rol, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nombre, $email, $telefono, $rol, $password);
    
    if ($stmt->execute()) {
        $success = 'Usuario agregado correctamente';
    } else {
        $error = 'Error al agregar usuario';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $id = (int)$_POST['id'];
    $nombre = sanitize($_POST['nombre']);
    $email = sanitize($_POST['email']);
    $telefono = sanitize($_POST['telefono']);
    $rol = sanitize($_POST['rol']);
    $comision_venta = (float)$_POST['comision_venta'];
    $comision_presentismo = (float)$_POST['comision_presentismo'];
    $comision_especial = (float)$_POST['comision_especial'];
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=?, telefono=?, rol=?, password=?, comision_venta=?, comision_presentismo=?, comision_especial=? WHERE id=?");
        $stmt->bind_param("sssssdddi", $nombre, $email, $telefono, $rol, $password, $comision_venta, $comision_presentismo, $comision_especial, $id);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=?, telefono=?, rol=?, comision_venta=?, comision_presentismo=?, comision_especial=? WHERE id=?");
        $stmt->bind_param("ssssdddi", $nombre, $email, $telefono, $rol, $comision_venta, $comision_presentismo, $comision_especial, $id);
    }
    
    if ($stmt->execute()) {
        $success = 'Usuario actualizado correctamente';
        $editando = 0;
    } else {
        $error = 'Error al actualizar usuario';
    }
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if ($id != $_SESSION['usuario_id']) {
        $conn->query("DELETE FROM usuarios WHERE id = $id");
    }
    redirect('usuarios/listar.php');
}

$usuarios = getAllUsers();
$usuario_edit = $editando > 0 ? getUserById($editando) : null;
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-people"></i> Gestión de Usuarios</h1>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-<?= $editando ? 'warning' : 'primary' ?> text-white">
                    <h5 class="mb-0"><?= $editando ? 'Editar Usuario' : 'Nuevo Usuario' ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $editando ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="<?= $usuario_edit['nombre'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= $usuario_edit['email'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= $usuario_edit['telefono'] ?? '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select name="rol" class="form-select" required>
                                <option value="admin" <?= ($usuario_edit['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <option value="tecnico" <?= ($usuario_edit['rol'] ?? '') === 'tecnico' ? 'selected' : '' ?>>Técnico</option>
                                <option value="ventas" <?= ($usuario_edit['rol'] ?? '') === 'ventas' ? 'selected' : '' ?>>Ventas</option>
                            </select>
                        </div>
                        
                        <?php if ($editando): ?>
                        <h6 class="mt-4 mb-3">Comisiones (%)</h6>
                        <div class="mb-2">
                            <label class="form-label">Por Venta</label>
                            <input type="number" name="comision_venta" class="form-control" step="0.01" value="<?= $usuario_edit['comision_venta'] ?? 10 ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Presentismo</label>
                            <input type="number" name="comision_presentismo" class="form-control" step="0.01" value="<?= $usuario_edit['comision_presentismo'] ?? 5 ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Especial</label>
                            <input type="number" name="comision_especial" class="form-control" step="0.01" value="<?= $usuario_edit['comision_especial'] ?? 15 ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label"><?= $editando ? 'Nueva Contraseña (dejar vacío para mantener)' : 'Contraseña' ?></label>
                            <input type="password" name="password" class="form-control" <?= $editando ? '' : 'required' ?>>
                        </div>
                        
                        <button type="submit" name="<?= $editando ? 'actualizar' : 'agregar' ?>" class="btn btn-<?= $editando ? 'warning' : 'success' ?> w-100">
                            <i class="bi bi-<?= $editando ? 'check' : 'plus' ?>"></i> <?= $editando ? 'Actualizar' : 'Crear Usuario' ?>
                        </button>
                        
                        <?php if ($editando): ?>
                        <a href="listar.php" class="btn btn-secondary w-100 mt-2">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Usuarios Registrados</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Rol</th>
                                <th>Comisiones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $usuarios->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['nombre']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= $u['telefono'] ?? '-' ?></td>
                                <td>
                                    <span class="badge bg-<?= match($u['rol']) { 'admin'=>'danger','tecnico'=>'primary','ventas'=>'success' } ?>">
                                        <?= ucfirst($u['rol']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        V: <?= $u['comision_venta'] ?? 10 ?>%<br>
                                        P: <?= $u['comision_presentismo'] ?? 5 ?>%<br>
                                        E: <?= $u['comision_especial'] ?? 15 ?>%
                                    </small>
                                </td>
                                <td>
                                    <a href="?editar=<?= $u['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                    <a href="?eliminar=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar usuario?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
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
</div>
<?php require_once '../include/footer.php'; ?>