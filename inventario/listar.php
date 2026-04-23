<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Inventario';
$search = $_GET['search'] ?? '';
$alerta = $_GET['alerta'] ?? '';
$repuestos = $alerta ? getRepuestosStockBajo() : getAllRepuestos($search);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-box-seam"></i> Inventario</h1>
        <a href="agregar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Repuesto
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por código, nombre o categoría..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <a href="listar.php" class="btn btn-outline-secondary">Ver Todo</a>
                        <a href="listar.php?alerta=1" class="btn btn-outline-warning"><i class="bi bi-exclamation-triangle"></i> Stock Bajo</a>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Stock Mín.</th>
                            <th>Precio Compra</th>
                            <th>Precio Venta</th>
                            <th>Ubicación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($rep = $repuestos->fetch_assoc()): ?>
                        <tr class="<?= $rep['stock'] <= $rep['stock_minimo'] ? 'table-warning' : '' ?>">
                            <td><strong><?= htmlspecialchars($rep['codigo']) ?></strong></td>
                            <td><?= htmlspecialchars($rep['nombre']) ?></td>
                            <td><?= htmlspecialchars($rep['categoria'] ?? '-') ?></td>
                            <td>
                                <?php if ($rep['stock'] <= $rep['stock_minimo']): ?>
                                <span class="badge bg-danger"><?= $rep['stock'] ?></span>
                                <?php else: ?>
                                <span class="badge bg-success"><?= $rep['stock'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $rep['stock_minimo'] ?></td>
                            <td><?= formatMoney($rep['precio_compra']) ?></td>
                            <td><?= formatMoney($rep['precio_venta']) ?></td>
                            <td><?= htmlspecialchars($rep['ubicacion'] ?? '-') ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#agregarStockModal<?= $rep['id'] ?>" title="Agregar Stock">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                                <a href="historial.php?id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-info" title="Historial">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                            </td>
                        </tr>
                        <div class="modal fade" id="agregarStockModal<?= $rep['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Agregar Stock - <?= htmlspecialchars($rep['nombre']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="agregar_stock.php">
                                        <div class="modal-body">
                                            <input type="hidden" name="repuesto_id" value="<?= $rep['id'] ?>">
                                            <div class="mb-3">
                                                <label for="cantidad" class="form-label">Cantidad</label>
                                                <input type="number" id="cantidad" name="cantidad" class="form-control" min="1" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="nota" class="form-label">Nota</label>
                                                <textarea id="nota" name="nota" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-success">Agregar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php if ($repuestos->num_rows == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No se encontraron repuestos</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>