<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../include/funciones.php';
require_once '../include/header.php';

if (!isLoggedIn()) {
    redirect('../autenticacion/login.php');
}

$page_title = 'Detalle de Orden';
$id = $_GET['id'] ?? 0;

$orden = getOrdenById($id);
if (!$orden) {
    redirect('ordenes/listar.php');
}

$historial = getHistorialEstados($id);
$repuestos = getRepuestosByOrden($id);
$factura = $conn->query("SELECT * FROM facturas WHERE orden_id = $id")->fetch_assoc();

$tecnicos = getAllUsers();
$repuestos_disp = getAllRepuestos();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-ticket-detailed"></i> Orden: <?= $orden['codigo'] ?></h1>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información de la Orden</h5>
                    <span class="badge" style="background-color: <?= COLORES_ESTADO[$orden['estado']] ?? '#6c757d' ?>; font-size: 1rem;">
                        <?= ESTADOS_ORDEN[$orden['estado']] ?? $orden['estado'] ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre']) ?></p>
                            <p><strong>Teléfono:</strong> <?= htmlspecialchars($orden['cliente_telefono']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($orden['cliente_email'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Equipo:</strong> <?= htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) ?></p>
                            <p><strong>Tipo:</strong> <?= ucfirst($orden['tipo_equipo']) ?></p>
                            <p><strong>Serie:</strong> <?= htmlspecialchars($orden['serie'] ?? '-') ?></p>
                            <p><strong>Técnico:</strong> <?= htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar') ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Prioridad:</strong> 
                                <span class="badge bg-<?= $orden['prioridad'] === 'urgente' ? 'danger' : ($orden['prioridad'] === 'alta' ? 'warning' : 'secondary') ?>">
                                    <?= PRIORIDADES[$orden['prioridad']] ?? $orden['prioridad'] ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Fecha Ingreso:</strong> <?= date('d/m/Y H:i', strtotime($orden['fecha_ingreso'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Tiempo Estimado:</strong> <?= $orden['tiempo_estimado'] ?? 0 ?> días</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Costo Diagnóstico:</strong> <?= formatMoney($orden['costo_diagnostico']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Costo Reparación:</strong> <?= formatMoney($orden['costo_reparacion']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Costo Total:</strong> <strong><?= formatMoney($orden['costo_total']) ?></strong></p>
                            <?php 
                            $has_garantia = $conn->query("SELECT id FROM garantias WHERE orden_id = $id")->num_rows > 0;
                            if ($orden['estado'] === 'entregado' && !$has_garantia): ?>
                            <a href="../garantias/agregar.php?orden_id=<?= $id ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-shield-check"></i> Agregar Garantía
                            </a>
                            <?php endif; ?>
                            <?php if ($has_garantia): ?>
                            <a href="../garantias/listar.php" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-shield-check"></i> Ver Garantía
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-journal-text"></i> Diagnóstico y Solución</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="actualizar_diagnostico.php">
                        <input type="hidden" name="orden_id" value="<?= $id ?>">
                        <div class="mb-3">
                            <label for="diagnostico" class="form-label">Diagnóstico</label>
                            <textarea id="diagnostico" name="diagnostico" class="form-control" rows="3"><?= htmlspecialchars($orden['diagnostico'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="solucion" class="form-label">Solución</label>
                            <textarea id="solucion" name="solucion" class="form-control" rows="3"><?= htmlspecialchars($orden['solucion'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="costo_reparacion" class="form-label">Costo Reparación</label>
                            <div class="input-group" style="max-width: 300px;">
                                <span class="input-group-text">S/</span>
                                <input type="number" id="costo_reparacion" name="costo_reparacion" class="form-control" value="<?= $orden['costo_reparacion'] ?>" min="0" step="0.01">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> Repuestos Utilizados</h5>
                    <?php if ($orden['estado'] !== 'entregado' && $orden['estado'] !== 'cancelado'): ?>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#agregarRepuestoModal">
                        <i class="bi bi-plus"></i> Agregar
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($repuestos->num_rows > 0): ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_rep = 0; while ($rep = $repuestos->fetch_assoc()): $total_rep += $rep['cantidad'] * $rep['precio_unitario']; ?>
                                <tr>
                                    <td><?= htmlspecialchars($rep['codigo']) ?></td>
                                    <td><?= htmlspecialchars($rep['nombre']) ?></td>
                                    <td><?= $rep['cantidad'] ?></td>
                                    <td><?= formatMoney($rep['precio_unitario']) ?></td>
                                    <td><?= formatMoney($rep['cantidad'] * $rep['precio_unitario']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total Repuestos:</strong></td>
                                    <td><strong><?= formatMoney($total_rep) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted mb-0">No se han utilizado repuestos</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Estados</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php while ($h = $historial->fetch_assoc()): ?>
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="me-3">
                                    <span class="badge" style="background-color: <?= COLORES_ESTADO[$h['estado']] ?? '#6c757d' ?>">
                                        <?= ESTADOS_ORDEN[$h['estado']] ?? $h['estado'] ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="mb-0"><?= htmlspecialchars($h['descripcion'] ?? '') ?></p>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($h['fecha'])) ?> - <?= htmlspecialchars($h['tecnico_nombre'] ?? 'Sistema') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($orden['estado'] !== 'entregado' && $orden['estado'] !== 'cancelado'): ?>
                        <a href="cambiar_estado.php?id=<?= $id ?>" class="btn btn-primary">
                            <i class="bi bi-arrow-repeat"></i> Cambiar Estado
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($orden['estado'] === 'reparado' && !$factura): ?>
                        <a href="../facturacion/crear.php?orden_id=<?= $id ?>" class="btn btn-success">
                            <i class="bi bi-receipt"></i> Generar Factura
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($factura): ?>
                        <a href="../facturacion/ver.php?id=<?= $factura['id'] ?>" class="btn btn-outline-success">
                            <i class="bi bi-file-text"></i> Ver Factura
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Notas</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nota del Cliente:</strong></p>
                    <p class="text-muted"><?= htmlspecialchars($orden['nota_cliente'] ?? 'Sin notas') ?></p>
                    <hr>
                    <p><strong>Observaciones:</strong></p>
                    <form method="POST" action="actualizar_observaciones.php">
                        <input type="hidden" name="orden_id" value="<?= $id ?>">
                        <textarea name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($orden['observaciones'] ?? '') ?></textarea>
                        <button type="submit" class="btn btn-sm btn-primary mt-2">Guardar</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock"></i> Fechas</h5>
                </div>
                <div class="card-body">
                    <p><strong>Recibido:</strong> <?= date('d/m/Y H:i', strtotime($orden['fecha_ingreso'])) ?></p>
                    <?php if ($orden['fecha_diagnostico']): ?>
                    <p><strong>Diagnóstico:</strong> <?= date('d/m/Y H:i', strtotime($orden['fecha_diagnostico'])) ?></p>
                    <?php endif; ?>
                    <?php if ($orden['fecha_reparacion']): ?>
                    <p><strong>Reparación:</strong> <?= date('d/m/Y H:i', strtotime($orden['fecha_reparacion'])) ?></p>
                    <?php endif; ?>
                    <?php if ($orden['fecha_entrega']): ?>
                    <p><strong>Entregado:</strong> <?= date('d/m/Y H:i', strtotime($orden['fecha_entrega'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="agregarRepuestoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Repuesto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="agregar_repuesto.php">
                <div class="modal-body">
                    <input type="hidden" name="orden_id" value="<?= $id ?>">
                    <div class="mb-3">
                        <label for="repuesto_id" class="form-label">Repuesto</label>
                        <select id="repuesto_id" name="repuesto_id" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php while ($r = $repuestos_disp->fetch_assoc()): ?>
                            <option value="<?= $r['id'] ?>" data-stock="<?= $r['stock'] ?>">
                                <?= htmlspecialchars($r['nombre'] . ' (Stock: ' . $r['stock'] . ')') ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" class="form-control" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>