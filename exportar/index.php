<?php
require_once __DIR__ . '/../config/database.php';
require_once  __DIR__ . '/../config/config.php';
require_once  __DIR__ . '/../include/funciones.php';


if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$page_title = 'Exportar Datos';

$conn = getConnection();

// Exportar clientes
if (isset($_POST['export_clientes'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=clientes.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nombre', 'Email', 'Teléfono', 'DNI', 'Dirección', 'Fecha Registro']);
    
    $result = $conn->query("SELECT * FROM clientes ORDER BY nombre");
    while($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Exportar inventario
if (isset($_POST['export_inventario'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=inventario.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Código', 'Nombre', 'Categoría', 'Stock', 'Stock Mín', 'Precio Compra', 'Precio Venta']);
    
    $result = $conn->query("SELECT * FROM repuestos ORDER BY categoria, nombre");
    while($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Exportar órdenes
if (isset($_POST['export_ordenes'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=ordenes.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Código', 'Cliente', 'Equipo', 'Estado', 'Fecha Ingreso', 'Costo Total']);
    
    $result = $conn->query("SELECT o.*, c.nombre as cliente_nombre, e.marca, e.modelo FROM ordenes_servicio o JOIN clientes c ON o.cliente_id = c.id JOIN equipos e ON o.equipo_id = e.id ORDER BY o.fecha_ingreso DESC");
    while($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['codigo'], $row['cliente_nombre'], $row['marca'].' '.$row['modelo'], $row['estado'], $row['fecha_ingreso'], $row['costo_total']]);
    }
    fclose($output);
    exit;
}

require_once  '../include/header.php';
?>
<div class="container-fluid">
    <h1><i class="bi bi-download"></i> Exportar Datos</h1>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-people"></i> Clientes
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">Exportar lista de clientes</p>
                    <form method="POST">
                        <button type="submit" name="export_clientes" class="btn btn-primary">
                            <i class="bi bi-download"></i> Descargar CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-box-seam"></i> Inventario
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">Exportar repuestos</p>
                    <form method="POST">
                        <button type="submit" name="export_inventario" class="btn btn-success">
                            <i class="bi bi-download"></i> Descargar CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <i class="bi bi-ticket-detailed"></i> Órdenes
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">Exportar órdenes de servicio</p>
                    <form method="POST">
                        <button type="submit" name="export_ordenes" class="btn btn-warning">
                            <i class="bi bi-download"></i> Descargar CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../include/footer.php'; ?>