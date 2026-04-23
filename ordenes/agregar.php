<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nueva Orden de Servicio';
$cliente_id = $_GET['cliente_id'] ?? 0;
$equipo_id = $_GET['equipo_id'] ?? 0;

$clientes = getAllClientes();
$tecnicos = getAllUsers();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $equipo_id = (int)$_POST['equipo_id'];
    $tecnico_id = (int)$_POST['tecnico_id'] ?: null;
    $prioridad = sanitize($_POST['prioridad']);
    $nota_cliente = sanitize($_POST['nota_cliente']);
    $tiempo_estimado = (int)$_POST['tiempo_estimado'];
    $costo_diagnostico = (float)$_POST['costo_diagnostico'];
    
    if (empty($cliente_id) || empty($equipo_id)) {
        $error = 'El cliente y equipo son obligatorios';
    } else {
        $codigo = generateCodigoOrden();
        
        $tecnico_id_val = $tecnico_id > 0 ? $tecnico_id : "NULL";
        
        $sql = "INSERT INTO ordenes_servicio (codigo, equipo_id, cliente_id, tecnico_id, prioridad, nota_cliente, tiempo_estimado, costo_diagnostico, estado) 
                VALUES ('$codigo', $equipo_id, $cliente_id, $tecnico_id_val, '$prioridad', '$nota_cliente', $tiempo_estimado, $costo_diagnostico, 'recibido')";
        
        if ($conn->query($sql)) {
            $orden_id = $conn->insert_id;
            
            require_once '../include/audit_helper.php';
            logOrdenCreate($conn, $orden_id, [
                'codigo' => $codigo,
                'cliente_id' => $cliente_id,
                'equipo_id' => $equipo_id,
                'prioridad' => $prioridad
            ]);
            
            $sql2 = "INSERT INTO estados_seguimiento (orden_id, estado, descripcion, tecnico_id, fecha) VALUES ($orden_id, 'recibido', 'Orden creada', {$_SESSION['usuario_id']}, NOW())";
            $conn->query($sql2);
            
            sendNotification($cliente_id, $orden_id, 'recibido');
            
            // Enviar email de notificación
            require_once '../include/email_helper.php';
            emailOrdenNueva($orden_id);
            
            redirect('ordenes/ver.php?id=' . $orden_id);
        } else {
            $error = 'Error al crear la orden';
        }
    }
}

$equipos = $equipo_id ? [$equipo_id => getEquipoById($equipo_id)] : [];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-plus-circle"></i> Nueva Orden de Servicio</h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" id="ordenForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cliente_id" class="form-label">Cliente *</label>
                            <select id="cliente_id" name="cliente_id" id="clienteSelect" class="form-select" required onchange="cargarEquipos()">
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
                            <label for="equipo_id" class="form-label">Equipo *</label>
                            <select id="equipo_id" name="equipo_id" id="equipoSelect" class="form-select" required>
                                <option value="">Seleccionar equipo...</option>
                                <?php if ($equipo_id && $equipo = getEquipoById($equipo_id)): ?>
                                <option value="<?= $equipo['id'] ?>" selected>
                                    <?= htmlspecialchars($equipo['marca'] . ' ' . $equipo['modelo'] . ' - ' . $equipo['serie']) ?>
                                </option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tecnico_id" class="form-label">Técnico Asignado</label>
                            <select id="tecnico_id" name="tecnico_id" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php while ($tec = $tecnicos->fetch_assoc()): ?>
                                <option value="<?= $tec['id'] ?>"><?= htmlspecialchars($tec['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="prioridad" class="form-label">Prioridad</label>
                            <select id="prioridad" name="prioridad" class="form-select">
                                <option value="normal">Normal</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                                <option value="baja">Baja</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="tiempo_estimado" class="form-label">Días Estimados</label>
                            <input type="number" id="tiempo_estimado" name="tiempo_estimado" class="form-control" value="3" min="1">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="costo_diagnostico" class="form-label">Costo Diagnóstico</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" id="costo_diagnostico" name="costo_diagnostico" class="form-control" value="0" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="nota_cliente" class="form-label">Notas del Cliente</label>
                    <textarea id="nota_cliente" name="nota_cliente" class="form-control" rows="2" placeholder="Problema reportado por el cliente..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Crear Orden
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function cargarEquipos() {
    const clienteId = document.getElementById('clienteSelect').value;
    const equipoSelect = document.getElementById('equipoSelect');
    
    if (!clienteId) {
        equipoSelect.innerHTML = '<option value="">Seleccionar equipo...</option>';
        return;
    }
    
    equipoSelect.innerHTML = '<option value="">Cargando...</option>';
    
    fetch('<?= BASE_URL ?>api/equipos.php?cliente_id=' + clienteId)
        .then(response => response.json())
        .then(data => {
            equipoSelect.innerHTML = '<option value="">Seleccionar equipo...</option>';
            data.forEach(equipo => {
                const option = document.createElement('option');
                option.value = equipo.id;
                option.textContent = equipo.marca + ' ' + (equipo.modelo || '') + ' - ' + (equipo.serie || 'Sin serie');
                equipoSelect.appendChild(option);
            });
        });
}
</script>
<?php require_once '../include/footer.php'; ?>