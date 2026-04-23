<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/header.php';
require_once '../include/funciones.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nuevo Presupuesto';
$error = '';
$success = '';

function generateNumeroPresupuesto() {
    $conn = getConnection();
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) as total FROM presupuestos WHERE numero_presupuesto LIKE 'P-$year-%'");
    $row = $result->fetch_assoc();
    $num = ($row['total'] ?? 0) + 1;
    return 'P-' . $year . '-' . str_pad($num, 6, '0', STR_PAD_LEFT);
}

$orden_id = $_GET['orden_id'] ?? 0;
$orden = $orden_id ? getOrdenById($orden_id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $orden_id = (int)$_POST['orden_id'];
    $descripciones = $_POST['descripcion'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $precios = $_POST['precio_unitario'] ?? [];
    $validez_dias = (int)$_POST['validez_dias'] ?: 15;
    $observaciones = sanitize($_POST['observaciones']);
    
    if (empty($descripciones) || count($descripciones) === 0) {
        $error = 'Debe agregar al menos un item';
    } else {
        $subtotal = 0;
        $items = [];
        for ($i = 0; $i < count($descripciones); $i++) {
            if (!empty($descripciones[$i])) {
                $cant = (int)$cantidades[$i] ?: 1;
                $prec = (float)$precios[$i] ?: 0;
                $imp = $cant * $prec;
                $subtotal += $imp;
                $items[] = [
                    'descripcion' => sanitize($descripciones[$i]),
                    'cantidad' => $cant,
                    'precio_unitario' => $prec,
                    'importe' => $imp
                ];
            }
        }
        
        if (empty($items)) {
            $error = 'Debe agregar al menos un item válido';
        } else {
            $igv = $subtotal * (IGV_PORCENTAJE / 100);
            $total = $subtotal + $igv;
            $numero = generateNumeroPresupuesto();
            
            $stmt = $conn->prepare("INSERT INTO presupuestos (numero_presupuesto, orden_id, cliente_id, subtotal, igv, total, validez_dias, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siidddis", $numero, $orden_id, $cliente_id, $subtotal, $igv, $total, $validez_dias, $observaciones);
            
            if ($stmt->execute()) {
                $presupuesto_id = $conn->insert_id;
                
                foreach ($items as $item) {
                    $stmt2 = $conn->prepare("INSERT INTO detalle_presupuesto (presupuesto_id, descripcion, cantidad, precio_unitario, importe) VALUES (?, ?, ?, ?, ?)");
                    $stmt2->bind_param("isidd", $presupuesto_id, $item['descripcion'], $item['cantidad'], $item['precio_unitario'], $item['importe']);
                    $stmt2->execute();
                }
                
                $success = 'Presupuesto creado correctamente';
                redirect('presupuestos/ver.php?id=' . $presupuesto_id);
            } else {
                $error = 'Error al crear el presupuesto';
            }
        }
    }
}

$clientes = getAllClientes();
$ordenes = $conn->query("SELECT o.id, o.codigo, c.nombre as cliente_nombre, e.marca, e.modelo FROM ordenes_servicio o JOIN clientes c ON o.cliente_id = c.id JOIN equipos e ON o.equipo_id = e.id WHERE o.estado = 'reparado' AND o.estado_orden = 'cerrada' AND NOT EXISTS (SELECT 1 FROM facturas f WHERE f.orden_id = o.id) AND NOT EXISTS (SELECT 1 FROM presupuestos p WHERE p.orden_id = o.id AND p.estado = 'convertido') ORDER BY o.fecha_ingreso DESC");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-text"></i> Nuevo Presupuesto</h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" id="presupuestoForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cliente_id" class="form-label">Cliente *</label>
                            <select id="cliente_id" name="cliente_id" class="form-select" required>
                                <option value="">Seleccionar cliente...</option>
                                <?php while ($c = $clientes->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>" data-dni="<?= $c['dni'] ?? '' ?>" data-telefono="<?= $c['telefono'] ?? '' ?>" <?= ($orden && $orden['cliente_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?> - <?= $c['dni'] ?? 'Sin DNI' ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="orden_id" class="form-label">Orden de Servicio (opcional)</label>
                            <select id="orden_id" name="orden_id" class="form-select">
                                <option value="0">Sin orden vinculada</option>
                                <?php while ($o = $ordenes->fetch_assoc()): ?>
                                <option value="<?= $o['id'] ?>" <?= ($orden_id == $o['id']) ? 'selected' : '' ?>>
                                    <?= $o['codigo'] ?> - <?= htmlspecialchars($o['cliente_nombre']) ?> (<?= $o['marca'] . ' ' . $o['modelo'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">Items del Presupuesto</h5>
                <div id="itemsContainer">
                    <div class="row item-row mb-2">
                        <div class="col-md-5">
                            <input type="text" name="descripcion[]" class="form-control" placeholder="Descripción del servicio/repuesto" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad" value="1" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="precio_unitario[]" class="form-control" placeholder="Precio" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control item-importe" value="S/ 0.00" readonly>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-remove-item" disabled><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="addItemBtn">
                    <i class="bi bi-plus"></i> Agregar Item
                </button>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="validez_dias" class="form-label">Validez (días)</label>
                            <input type="number" id="validez_dias" name="validez_dias" class="form-control" value="15" min="1">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-8 text-end">
                        <strong>Subtotal:</strong>
                    </div>
                    <div class="col-md-4 text-end">
                        <span id="subtotalDisplay">S/ 0.00</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 text-end">
                        IGV (<?= IGV_PORCENTAJE ?>%):
                    </div>
                    <div class="col-md-4 text-end">
                        <span id="igvDisplay">S/ 0.00</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 text-end">
                        <strong>Total:</strong>
                    </div>
                    <div class="col-md-4 text-end">
                        <strong id="totalDisplay">S/ 0.00</strong>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Crear Presupuesto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addItemBtn').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'row item-row mb-2';
    newRow.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="descripcion[]" class="form-control" placeholder="Descripción del servicio/repuesto" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad" value="1" min="1" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="precio_unitario[]" class="form-control" placeholder="Precio" step="0.01" min="0" required>
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control item-importe" value="S/ 0.00" readonly>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-remove-item"><i class="bi bi-trash"></i></button>
        </div>
    `;
    container.appendChild(newRow);
    attachRowListeners(newRow);
    updateButtons();
});

function attachRowListeners(row) {
    const cantidadInput = row.querySelector('input[name="cantidad[]"]');
    const precioInput = row.querySelector('input[name="precio_unitario[]"]');
    const removeBtn = row.querySelector('.btn-remove-item');
    
    function updateImporte() {
        const cant = parseFloat(cantidadInput.value) || 0;
        const prec = parseFloat(precioInput.value) || 0;
        const imp = cant * prec;
        row.querySelector('.item-importe').value = 'S/ ' + imp.toFixed(2);
        calculateTotals();
    }
    
    cantidadInput.addEventListener('input', updateImporte);
    precioInput.addEventListener('input', updateImporte);
    removeBtn.addEventListener('click', function() {
        row.remove();
        updateButtons();
        calculateTotals();
    });
}

function updateButtons() {
    const rows = document.querySelectorAll('.item-row');
    const removeBtns = document.querySelectorAll('.btn-remove-item');
    removeBtns.forEach(btn => btn.disabled = rows.length <= 1);
}

function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const cant = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
        const prec = parseFloat(row.querySelector('input[name="precio_unitario[]"]').value) || 0;
        subtotal += cant * prec;
    });
    
    const igv = subtotal * <?= IGV_PORCENTAJE / 100 ?>;
    const total = subtotal + igv;
    
    document.getElementById('subtotalDisplay').textContent = 'S/ ' + subtotal.toFixed(2);
    document.getElementById('igvDisplay').textContent = 'S/ ' + igv.toFixed(2);
    document.getElementById('totalDisplay').textContent = 'S/ ' + total.toFixed(2);
}

document.querySelectorAll('.item-row').forEach(attachRowListeners);
updateButtons();
</script>
<?php require_once '../include/footer.php'; ?>