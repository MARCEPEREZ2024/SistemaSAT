<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nueva Cotización';
$error = '';

function generateNumeroCotizacion() {
    $conn = getConnection();
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) as total FROM cotizaciones WHERE numero_cotizacion LIKE 'COT-$year-%'");
    $row = $result->fetch_assoc();
    $num = ($row['total'] ?? 0) + 1;
    return 'COT-' . $year . '-' . str_pad($num, 6, '0', STR_PAD_LEFT);
}

$orden_id = $_GET['orden_id'] ?? 0;
$orden = $orden_id ? getOrdenById($orden_id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $equipo_id = (int)$_POST['equipo_id'];
    $descripcion = sanitize($_POST['descripcion']);
    $validez_dias = (int)$_POST['validez_dias'] ?: 7;
    $observaciones = sanitize($_POST['observaciones']);
    
    $descripciones = $_POST['item_descripcion'] ?? [];
    $cantidades = $_POST['item_cantidad'] ?? [];
    $precios = $_POST['item_precio'] ?? [];
    
    $items = [];
    $subtotal = 0;
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
        $error = 'Debe agregar al menos un item';
    } else {
        $numero = generateNumeroCotizacion();
        $stmt = $conn->prepare("INSERT INTO cotizaciones (numero_cotizacion, cliente_id, equipo_id, descripcion, validez_dias, observaciones) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisis", $numero, $cliente_id, $equipo_id, $descripcion, $validez_dias, $observaciones);
        
        if ($stmt->execute()) {
            $cotizacion_id = $conn->insert_id;
            
            foreach ($items as $item) {
                $stmt2 = $conn->prepare("INSERT INTO detalle_cotizacion (cotizacion_id, descripcion, cantidad, precio_unitario, importe) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("isidd", $cotizacion_id, $item['descripcion'], $item['cantidad'], $item['precio_unitario'], $item['importe']);
                $stmt2->execute();
            }
            
            redirect('ver.php?id=' . $cotizacion_id);
        } else {
            $error = 'Error al crear la cotización';
        }
    }
}

$clientes = getAllClientes();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-ruled"></i> Nueva Cotización</h1>
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
                            <label class="form-label">Cliente *</label>
                            <select name="cliente_id" class="form-select" required>
                                <option value="">Seleccionar cliente...</option>
                                <?php while ($c = $clientes->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>" <?= ($orden && $orden['cliente_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?> - <?= $c['dni'] ?? '' ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Equipo (opcional)</label>
                            <input type="text" class="form-control" placeholder="Marca - Modelo" value="<?= $orden ? htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) : '' ?>">
                            <input type="hidden" name="equipo_id" value="<?= $orden_id ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Descripción del trabajo</label>
                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Describe el trabajo a realizar..."></textarea>
                </div>
                
                <h5 class="mt-4 mb-3">Items de la Cotización</h5>
                <div id="itemsContainer">
                    <div class="row item-row mb-2">
                        <div class="col-md-5">
                            <input type="text" name="item_descripcion[]" class="form-control" placeholder="Descripción" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="item_cantidad[]" class="form-control" placeholder="Cantidad" value="1" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="item_precio[]" class="form-control" placeholder="Precio" step="0.01" required>
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
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Validez (días)</label>
                            <input type="number" name="validez_dias" class="form-control" value="7" min="1">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-8 text-end"><strong>Total Estimado:</strong></div>
                    <div class="col-md-4 text-end"><strong id="totalDisplay">S/ 0.00</strong></div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Crear Cotización
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
            <input type="text" name="item_descripcion[]" class="form-control" placeholder="Descripción" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="item_cantidad[]" class="form-control" placeholder="Cantidad" value="1" min="1" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="item_precio[]" class="form-control" placeholder="Precio" step="0.01" required>
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control item-importe" value="S/ 0.00" readonly>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-remove-item"><i class="bi bi-trash"></i></button>
        </div>
    `;
    container.appendChild(newRow);
    attachListeners(newRow);
    updateButtons();
});

function attachListeners(row) {
    const cant = row.querySelector('input[name="item_cantidad[]"]');
    const prec = row.querySelector('input[name="item_precio[]"]');
    const removeBtn = row.querySelector('.btn-remove-item');
    
    function update() {
        const imp = (parseFloat(cant.value) || 0) * (parseFloat(prec.value) || 0);
        row.querySelector('.item-importe').value = 'S/ ' + imp.toFixed(2);
        calculateTotal();
    }
    
    cant.addEventListener('input', update);
    prec.addEventListener('input', update);
    removeBtn.addEventListener('click', function() { row.remove(); updateButtons(); calculateTotal(); });
}

function updateButtons() {
    const rows = document.querySelectorAll('.item-row');
    document.querySelectorAll('.btn-remove-item').forEach(b => b.disabled = rows.length <= 1);
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        total += (parseFloat(row.querySelector('input[name="item_cantidad[]"]').value) || 0) * 
                 (parseFloat(row.querySelector('input[name="item_precio[]"]').value) || 0);
    });
    document.getElementById('totalDisplay').textContent = 'S/ ' + total.toFixed(2);
}

document.querySelectorAll('.item-row').forEach(attachListeners);
updateButtons();
</script>
<?php require_once '../include/footer.php'; ?>