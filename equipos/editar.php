<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Editar Equipo';
$id = $_GET['id'] ?? 0;

$equipo = getEquipoById($id);
if (!$equipo) {
    redirect('equipos/listar.php');
}

$clientes = getAllClientes();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $marca = sanitize($_POST['marca']);
    $modelo = sanitize($_POST['modelo']);
    $serie = sanitize($_POST['serie']);
    $tipo_equipo = sanitize($_POST['tipo_equipo']);
    $diagnostico_inicial = sanitize($_POST['diagnostico_inicial']);
    $passwordBIOS = sanitize($_POST['passwordBIOS']);
    $passwordSO = sanitize($_POST['passwordSO']);
    $accesorios = sanitize($_POST['accesorios']);
    $estado_equipo = sanitize($_POST['estado_equipo']);
    $fecha_ingreso = $_POST['fecha_ingreso'];
    
    if (empty($cliente_id) || empty($marca)) {
        $error = 'El cliente y marca son obligatorios';
    } else {
        $stmt = $conn->prepare("UPDATE equipos SET cliente_id = ?, marca = ?, modelo = ?, serie = ?, tipo_equipo = ?, diagnostico_inicial = ?, passwordBIOS = ?, passwordSO = ?, accesorios = ?, estado_equipo = ?, fecha_ingreso = ? WHERE id = ?");
        $stmt->bind_param("issssssssssi", $cliente_id, $marca, $modelo, $serie, $tipo_equipo, $diagnostico_inicial, $passwordBIOS, $passwordSO, $accesorios, $estado_equipo, $fecha_ingreso, $id);
        
        if ($stmt->execute()) {
            require_once '../include/audit_helper.php';
            registrarAccion($conn, 'actualizar', 'equipos', $id, null, json_encode($_POST));
            
            redirect('equipos/listar.php');
        } else {
            $error = 'Error al actualizar el equipo';
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-pencil"></i> Editar Equipo</h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cliente_id" class="form-label">Cliente *</label>
                            <select id="cliente_id" name="cliente_id" class="form-select" required>
                                <option value="">Seleccionar cliente...</option>
                                <?php while ($cli = $clientes->fetch_assoc()): ?>
                                <option value="<?= $cli['id'] ?>" <?= $equipo['cliente_id'] == $cli['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cli['nombre'] . ' - ' . $cli['telefono']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipo_equipo" class="form-label">Tipo de Equipo</label>
                            <select id="tipo_equipo" name="tipo_equipo" class="form-select">
                                <option value="notebook" <?= $equipo['tipo_equipo'] == 'notebook' ? 'selected' : '' ?>>Notebook</option>
                                <option value="desktop" <?= $equipo['tipo_equipo'] == 'desktop' ? 'selected' : '' ?>>Desktop</option>
                                <option value="all-in-one" <?= $equipo['tipo_equipo'] == 'all-in-one' ? 'selected' : '' ?>>All-in-One</option>
                                <option value="monitor" <?= $equipo['tipo_equipo'] == 'monitor' ? 'selected' : '' ?>>Monitor</option>
                                <option value="otro" <?= $equipo['tipo_equipo'] == 'otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="marca" class="form-label">Marca *</label>
                            <input type="text" id="marca" name="marca" class="form-control" value="<?= htmlspecialchars($equipo['marca']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="text" id="modelo" name="modelo" class="form-control" value="<?= htmlspecialchars($equipo['modelo'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="serie" class="form-label">Número de Serie</label>
                            <input type="text" id="serie" name="serie" class="form-control" value="<?= htmlspecialchars($equipo['serie'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="passwordBIOS" class="form-label">Contraseña BIOS</label>
                            <input type="text" id="passwordBIOS" name="passwordBIOS" class="form-control" value="<?= htmlspecialchars($equipo['passwordBIOS'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="passwordSO" class="form-label">Contraseña SO</label>
                            <input type="text" id="passwordSO" name="passwordSO" class="form-control" value="<?= htmlspecialchars($equipo['passwordSO'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="estado_equipo" class="form-label">Estado Físico</label>
                            <select id="estado_equipo" name="estado_equipo" class="form-select">
                                <option value="bueno" <?= $equipo['estado_equipo'] == 'bueno' ? 'selected' : '' ?>>Bueno</option>
                                <option value="regular" <?= $equipo['estado_equipo'] == 'regular' ? 'selected' : '' ?>>Regular</option>
                                <option value="malo" <?= $equipo['estado_equipo'] == 'malo' ? 'selected' : '' ?>>Malo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                            <input type="date" id="fecha_ingreso" name="fecha_ingreso" class="form-control" value="<?= $equipo['fecha_ingreso'] ?>">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="diagnostico_inicial" class="form-label">Diagnóstico Inicial</label>
                    <textarea id="diagnostico_inicial" name="diagnostico_inicial" class="form-control" rows="2"><?= htmlspecialchars($equipo['diagnostico_inicial'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="accesorios" class="form-label">Accesorios</label>
                    <textarea id="accesorios" name="accesorios" class="form-control" rows="2"><?= htmlspecialchars($equipo['accesorios'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Actualizar Equipo
                </button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>