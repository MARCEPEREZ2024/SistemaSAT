<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nueva Factura';
$orden_id = $_GET['orden_id'] ?? 0;
$es_directa = isset($_GET['directa']) && $_GET['directa'] == 1;
$error = '';
$success = '';

$orden = $orden_id ? getOrdenById($orden_id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $es_factura_directa = isset($_POST['es_factura_directa']) && $_POST['es_factura_directa'] == 1;
    $cliente_id = (int)$_POST['cliente_id'];
    $orden_id_post = $es_factura_directa ? 0 : (int)$_POST['orden_id'];
    $tipo_pago = sanitize($_POST['tipo_pago']);
    $observaciones = sanitize($_POST['observaciones']);
    
    if (!$cliente_id) {
        $error = 'Debe seleccionar un cliente';
    } else {
        $descripciones = $_POST['descripcion'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios = $_POST['precio_unitario'] ?? [];
        
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
            if (!$es_factura_directa && $orden_id_post > 0) {
                $check = $conn->query("SELECT id FROM facturas WHERE orden_id = $orden_id_post");
                if ($check->num_rows > 0) {
                    $error = 'Ya existe una factura para esta orden';
                }
            }
            
            if (empty($error)) {
                $igv = $subtotal * (IGV_PORCENTAJE / 100);
                $total = $subtotal + $igv;
                $numero_factura = generateNumeroFactura();
                
                $stmt = $conn->prepare("INSERT INTO facturas (numero_factura, orden_id, cliente_id, subtotal, igv, total, tipo_pago, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siidddss", $numero_factura, $orden_id_post, $cliente_id, $subtotal, $igv, $total, $tipo_pago, $observaciones);
                
                if ($stmt->execute()) {
                    $factura_id = $conn->insert_id;
                    
                    foreach ($items as $item) {
                        $stmt2 = $conn->prepare("INSERT INTO detalle_factura (factura_id, descripcion, cantidad, precio_unitario, importe) VALUES (?, ?, ?, ?, ?)");
                        $stmt2->bind_param("isidd", $factura_id, $item['descripcion'], $item['cantidad'], $item['precio_unitario'], $item['importe']);
                        $stmt2->execute();
                    }
                    
                    redirect('ver.php?id=' . $factura_id);
                } else {
                    $error = 'Error al crear la factura';
                }
            }
        }
    }
}

$ordenes_disponibles = $conn->query("SELECT o.id, o.codigo, c.nombre as cliente_nombre, e.marca, e.modelo, o.costo_total FROM ordenes_servicio o JOIN clientes c ON o.cliente_id = c.id JOIN equipos e ON o.equipo_id = e.id WHERE o.estado = 'reparado' AND o.estado_orden = 'cerrada' AND NOT EXISTS (SELECT 1 FROM facturas f WHERE f.orden_id = o.id) ORDER BY o.fecha_reparacion DESC");
$clientes = getAllClientes();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-receipt"></i> Nueva Factura</h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" id="facturaForm">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_factura" id="tipoOrden" value="orden" checked onchange="toggleTipoFactura()">
                            <label class="form-check-label" for="tipoOrden">Factura desde Orden de Servicio</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_factura" id="tipoDirecta" value="directa" onchange="toggleTipoFactura()">
                            <label class="form-check-label" for="tipoDirecta">Factura Directa (desde cero)</label>
                        </div>
                    </div>
                </div>
                
                <div id="seccionOrden">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Orden de Servicio *</label>
                                <select name="orden_id" id="ordenSelect" class="form-select" onchange="cargarOrden()">
                                    <option value="">Seleccionar orden...</option>
                                    <?php while ($ord = $ordenes_disponibles->fetch_assoc()): ?>
                                    <option value="<?= $ord['id'] ?>" data-cliente="<?= htmlspecialchars($ord['cliente_nombre']) ?>" data-equipo="<?= htmlspecialchars($ord['marca'] . ' ' . $ord['modelo']) ?>" data-total="<?= $ord['costo_total'] ?>" <?= ($orden_id == $ord['id']) ? 'selected' : '' ?>>
                                        <?= $ord['codigo'] ?> - <?= htmlspecialchars($ord['cliente_nombre']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cliente</label>
                                <input type="text" id="clienteNombre" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="seccionDirecta" style="display: none;">
                    <input type="hidden" name="es_factura_directa" value="1">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cliente *</label>
                                <select name="cliente_id" id="clienteSelectDirecta" class="form-select" required>
                                    <option value="">Seleccionar cliente...</option>
                                    <?php while ($c = $clientes->fetch_assoc()): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> - <?= $c['dni'] ?? '' ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo de Pago</label>
                                <select name="tipo_pago" class="form-select">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="credito">Crédito</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Items de la Factura</h5>
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
                </div>
                
                <div id="ordenInfo" class="mt-4" style="display: none;">
                    <h5>Detalle de la Orden</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="detalleTable">
                            <thead>
                                <tr>
                                    <th>Descripción</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Importe</th>
                                </tr>
                            </thead>
                            <tbody id="detalleBody">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td id="subtotalDisplay">S/ 0.00</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">IGV (<?= IGV_PORCENTAJE ?>%):</td>
                                    <td id="igvDisplay">S/ 0.00</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td id="totalDisplay"><strong>S/ 0.00</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <div id="totalesDirecta" class="mt-4" style="display: none;">
                    <div class="row">
                        <div class="col-md-8 text-end"><strong>Subtotal:</strong></div>
                        <div class="col-md-4 text-end" id="subtotalDirecta">S/ 0.00</div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 text-end">IGV (<?= IGV_PORCENTAJE ?>%):</div>
                        <div class="col-md-4 text-end" id="igvDirecta">S/ 0.00</div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 text-end"><strong>Total:</strong></div>
                        <div class="col-md-4 text-end"><strong id="totalDirecta">S/ 0.00</strong></div>
                    </div>
                </div>
                
                <div class="mb-3 mt-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Generar Factura
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let ordenesData = {};

<?php
$ordenes_disponibles = $conn->query("SELECT o.id, o.codigo, c.nombre as cliente_nombre, e.marca, e.modelo, o.costo_diagnostico, o.costo_reparacion FROM ordenes_servicio o JOIN clientes c ON o.cliente_id = c.id JOIN equipos e ON o.equipo_id = e.id WHERE o.estado = 'reparado' AND o.estado_orden = 'cerrada' AND NOT EXISTS (SELECT 1 FROM facturas f WHERE f.orden_id = o.id) ORDER BY o.fecha_reparacion DESC");
$ordenesArray = [];
while ($ord = $ordenes_disponibles->fetch_assoc()) {
    $orden_id = $ord['id'];
    $repuestos_result = $conn->query("SELECT r.nombre, ro.cantidad, ro.precio_unitario FROM repuestos_orden ro JOIN repuestos r ON ro.repuesto_id = r.id WHERE ro.orden_id = $orden_id");
    $repuestos = [];
    $costo_repuestos = 0;
    while ($rep = $repuestos_result->fetch_assoc()) {
        $repuestos[] = $rep;
        $costo_repuestos += $rep['cantidad'] * $rep['precio_unitario'];
    }
    $ordenesArray[$orden_id] = [
        'diagnostico' => (float)($ord['costo_diagnostico'] ?? 0),
        'reparacion' => (float)($ord['costo_reparacion'] ?? 0),
        'repuestos' => $repuestos,
        'costo_repuestos' => (float)$costo_repuestos
    ];
}
echo "ordenesData = " . json_encode($ordenesArray, JSON_UNESCAPED_UNICODE) . ";\n";
?>

function toggleTipoFactura() {
    const esDirecta = document.getElementById('tipoDirecta').checked;
    document.getElementById('seccionOrden').style.display = esDirecta ? 'none' : 'block';
    document.getElementById('seccionDirecta').style.display = esDirecta ? 'block' : 'none';
    document.getElementById('ordenInfo').style.display = 'none';
    document.getElementById('totalesDirecta').style.display = esDirecta ? 'block' : 'none';
    
    if (!esDirecta) {
        cargarOrden();
    }
}

function cargarOrden() {
    const ordenId = document.getElementById('ordenSelect').value;
    const ordenInfo = document.getElementById('ordenInfo');
    const detalleBody = document.getElementById('detalleBody');
    
    if (!ordenId) {
        ordenInfo.style.display = 'none';
        return;
    }
    
    const selectedOption = document.getElementById('ordenSelect').options[document.getElementById('ordenSelect').selectedIndex];
    document.getElementById('clienteNombre').value = selectedOption.dataset.cliente || '';
    
    const data = ordenesData[ordenId] || { diagnostico: 0, reparacion: 0, repuestos: [], costo_repuestos: 0 };
    const diagnostico = parseFloat(data.diagnostico) || 0;
    const reparacion = parseFloat(data.reparacion) || 0;
    const costoRepuestos = parseFloat(data.costo_repuestos) || 0;
    const repuestos = data.repuestos || [];
    
    let html = '';
    let subtotal = 0;
    
    if (diagnostico > 0) {
        html += '<tr><td>Diagnóstico</td><td>1</td><td>S/ ' + diagnostico.toFixed(2) + '</td><td>S/ ' + diagnostico.toFixed(2) + '</td></tr>';
        subtotal += diagnostico;
    }
    
    if (reparacion > 0) {
        html += '<tr><td>Servicio de reparación</td><td>1</td><td>S/ ' + reparacion.toFixed(2) + '</td><td>S/ ' + reparacion.toFixed(2) + '</td></tr>';
        subtotal += reparacion;
    }
    
    if (repuestos.length > 0) {
        for (let i = 0; i < repuestos.length; i++) {
            const rep = repuestos[i];
            const importe = (parseFloat(rep.cantidad) || 0) * (parseFloat(rep.precio_unitario) || 0);
            html += '<tr><td>' + rep.nombre + ' (x' + rep.cantidad + ')</td><td>' + rep.cantidad + '</td><td>S/ ' + (parseFloat(rep.precio_unitario) || 0).toFixed(2) + '</td><td>S/ ' + importe.toFixed(2) + '</td></tr>';
            subtotal += importe;
        }
    } else if (costoRepuestos > 0) {
        html += '<tr><td>Repuestos utilizados</td><td>1</td><td>S/ ' + costoRepuestos.toFixed(2) + '</td><td>S/ ' + costoRepuestos.toFixed(2) + '</td></tr>';
        subtotal += costoRepuestos;
    }
    
    if (subtotal === 0) {
        html = '<tr><td colspan="4" class="text-center">Sin costos registrados</td></tr>';
    }
    
    detalleBody.innerHTML = html;
    
    const igv = subtotal * <?= IGV_PORCENTAJE / 100 ?>;
    const total = subtotal + igv;
    
    document.getElementById('subtotalDisplay').textContent = 'S/ ' + subtotal.toFixed(2);
    document.getElementById('igvDisplay').textContent = 'S/ ' + igv.toFixed(2);
    document.getElementById('totalDisplay').innerHTML = '<strong>S/ ' + total.toFixed(2) + '</strong>';
    
    ordenInfo.style.display = 'block';
}

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
        calculateTotalsDirecta();
    }
    
    cantidadInput.addEventListener('input', updateImporte);
    precioInput.addEventListener('input', updateImporte);
    removeBtn.addEventListener('click', function() {
        row.remove();
        updateButtons();
        calculateTotalsDirecta();
    });
}

function updateButtons() {
    const rows = document.querySelectorAll('.item-row');
    const removeBtns = document.querySelectorAll('.btn-remove-item');
    removeBtns.forEach(btn => btn.disabled = rows.length <= 1);
}

function calculateTotalsDirecta() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const cant = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
        const prec = parseFloat(row.querySelector('input[name="precio_unitario[]"]').value) || 0;
        subtotal += cant * prec;
    });
    
    const igv = subtotal * <?= IGV_PORCENTAJE / 100 ?>;
    const total = subtotal + igv;
    
    document.getElementById('subtotalDirecta').textContent = 'S/ ' + subtotal.toFixed(2);
    document.getElementById('igvDirecta').textContent = 'S/ ' + igv.toFixed(2);
    document.getElementById('totalDirecta').textContent = 'S/ ' + total.toFixed(2);
}

document.querySelectorAll('.item-row').forEach(attachRowListeners);
updateButtons();

<?php if ($orden_id > 0): ?>
document.getElementById('ordenSelect').value = '<?= $orden_id ?>';
cargarOrden();
<?php endif; ?>

<?php if ($es_directa): ?>
document.getElementById('tipoDirecta').checked = true;
toggleTipoFactura();
<?php endif; ?>
</script>
<?php require_once '../include/footer.php'; ?>