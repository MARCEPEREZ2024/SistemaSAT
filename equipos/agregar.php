<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nuevo Equipo';
$cliente_id = $_GET['cliente_id'] ?? 0;
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
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');
    
    if (empty($cliente_id) || empty($marca)) {
        $error = 'El cliente y marca son obligatorios';
    } else {
        $sql = "INSERT INTO equipos (cliente_id, marca, modelo, serie, tipo_equipo, diagnostico_inicial, passwordBIOS, passwordSO, accesorios, estado_equipo, fecha_ingreso, estado) VALUES ($cliente_id, '$marca', '$modelo', '$serie', '$tipo_equipo', '$diagnostico_inicial', '$passwordBIOS', '$passwordSO', '$accesorios', '$estado_equipo', '$fecha_ingreso', 'activo')";
        
        if ($conn->query($sql)) {
            $equipo_id = $conn->insert_id;
            
            require_once '../include/audit_helper.php';
            registrarAccion($conn, 'crear', 'equipos', $equipo_id, null, json_encode([
                'marca' => $marca,
                'modelo' => $modelo,
                'tipo_equipo' => $tipo_equipo,
                'cliente_id' => $cliente_id
            ]));
            
            redirect('equipos/listar.php');
        } else {
            $error = 'Error al registrar el equipo';
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-laptop"></i> Nuevo Equipo</h1>
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
                                <option value="<?= $cli['id'] ?>" <?= $cliente_id == $cli['id'] ? 'selected' : '' ?>>
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
                                <option value="notebook">Notebook</option>
                                <option value="desktop">Desktop</option>
                                <option value="all-in-one">All-in-One</option>
                                <option value="monitor">Monitor</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="marca" class="form-label">Marca *</label>
                            <input type="text" id="marca" name="marca" class="form-control" required placeholder="ej: Lenovo, HP, Dell">
                        </div>
                    </div>
                    <div class="col-md-4">
<div class="mb-3">
                        <label for="ubicacion" class="form-label">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" class="form-control" placeholder="ej: Estante A-1">
                    </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="serie" class="form-label">Número de Serie</label>
                            <input type="text" id="serie" name="serie" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="passwordBIOS" class="form-label">Contraseña BIOS</label>
                            <input type="text" id="passwordBIOS" name="passwordBIOS" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="passwordSO" class="form-label">Contraseña SO</label>
                            <input type="text" id="passwordSO" name="passwordSO" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="estado_equipo" class="form-label">Estado Físico</label>
                            <select id="estado_equipo" name="estado_equipo" class="form-select">
                                <option value="bueno">Bueno</option>
                                <option value="regular" selected>Regular</option>
                                <option value="malo">Malo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                            <input type="date" id="fecha_ingreso" name="fecha_ingreso" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="diagnostico_inicial" class="form-label">Diagnóstico Inicial</label>
                    <textarea id="diagnostico_inicial" name="diagnostico_inicial" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label for="accesorios" class="form-label">Accesorios</label>
                    <textarea id="accesorios" name="accesorios" class="form-control" rows="2" placeholder="ej: Cargador, Mouse, Bolsa"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar Equipo
                </button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>