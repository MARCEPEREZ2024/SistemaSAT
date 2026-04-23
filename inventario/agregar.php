<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Nuevo Repuesto';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = sanitize($_POST['codigo']);
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);
    $categoria = sanitize($_POST['categoria']);
    $marca_compatible = sanitize($_POST['marca_compatible']);
    $modelo_compatible = sanitize($_POST['modelo_compatible']);
    $stock = (int)$_POST['stock'];
    $stock_minimo = (int)$_POST['stock_minimo'];
    $precio_compra = (float)$_POST['precio_compra'];
    $precio_venta = (float)$_POST['precio_venta'];
    $ubicacion = sanitize($_POST['ubicacion']);
    
    if (empty($codigo) || empty($nombre)) {
        $error = 'El código y nombre son obligatorios';
    } else {
        $stmt = $conn->prepare("INSERT INTO repuestos (codigo, nombre, descripcion, categoria, marca_compatible, modelo_compatible, stock, stock_minimo, precio_compra, precio_venta, ubicacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssiddds", $codigo, $nombre, $descripcion, $categoria, $marca_compatible, $modelo_compatible, $stock, $stock_minimo, $precio_compra, $precio_venta, $ubicacion);
        
        if ($stmt->execute()) {
            $repuesto_id = $conn->insert_id;
            
            require_once '../include/audit_helper.php';
            registrarAccion($conn, 'crear', 'repuestos', $repuesto_id, null, json_encode([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'categoria' => $categoria,
                'stock' => $stock
            ]));
            
            if ($stock > 0) {
                $repuesto_id = $conn->insert_id;
                $stmt = $conn->prepare("INSERT INTO movimientos_inventario (repuesto_id, tipo, cantidad, usuario_id, nota, fecha) VALUES (?, 'entrada', ?, ?, 'Stock inicial', NOW())");
                $stmt->bind_param("iii", $repuesto_id, $stock, $_SESSION['usuario_id']);
                $stmt->execute();
            }
            redirect('inventario/listar.php');
        } else {
            $error = 'Error al registrar el repuesto';
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-plus-circle"></i> Nuevo Repuesto</h1>
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
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código *</label>
                            <input type="text" id="codigo" name="codigo" class="form-control" required placeholder="ej: DIS-001">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select id="categoria" name="categoria" class="form-select">
                                <option value="">Seleccionar...</option>
                                <option value="Pantallas">Pantallas</option>
                                <option value="Teclados">Teclados</option>
                                <option value="Baterías">Baterías</option>
                                <option value="Cargadores">Cargadores</option>
                                <option value="Discos">Discos</option>
                                <option value="Memorias">Memorias</option>
                                <option value="Placas">Placas</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="marca_compatible" class="form-label">Marca Compatible</label>
                            <input type="text" id="marca_compatible" name="marca_compatible" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="modelo_compatible" class="form-label">Modelo Compatible</label>
                            <input type="text" id="modelo_compatible" name="modelo_compatible" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock Inicial</label>
                            <input type="number" id="stock" name="stock" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                            <input type="number" id="stock_minimo" name="stock_minimo" class="form-control" value="5" min="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="precio_compra" class="form-label">Precio Compra</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" id="precio_compra" name="precio_compra" class="form-control" value="0" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="precio_venta" class="form-label">Precio Venta</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" id="precio_venta" name="precio_venta" class="form-control" value="0" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="ubicacion" class="form-label">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion" class="form-control" placeholder="ej: Estante A-1">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar Repuesto
                </button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>