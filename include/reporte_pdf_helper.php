<?php
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class ReportePDF extends TCPDF {
    public $headerTitle = 'Sistema SAT';
    public $sucursal = '';
    
    public function Header() {
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, $this->headerTitle, 0, true, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 6, $this->sucursal, 0, true, 'C');
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 5, 'Fecha: ' . date('d/m/Y H:i'), 0, true, 'R');
        $this->Line(10, 30, 200, 30);
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, false, 'C');
    }
    
    public function sectionTitle($title) {
        $this->SetFont('helvetica', 'B', 12);
        $this->SetFillColor(13, 110, 253);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, $title, 0, true, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(3);
    }
    
    public function tableHeader() {
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(240, 240, 240);
        $this->SetDrawColor(200, 200, 200);
    }
}

function generarReporteOrdenes($anio, $mes = null, $formato = 'pdf') {
    $conn = getConnection();
    
    $where = "WHERE YEAR(o.fecha_ingreso) = $anio";
    if ($mes) $where .= " AND MONTH(o.fecha_ingreso) = $mes";
    
    $pdf = new ReportePDF();
    $pdf->headerTitle = 'Reporte de Órdenes de Servicio';
    $pdf->sucursal = "Año: $anio" . ($mes ? " - Mes: $mes" : '');
    $pdf->AddPage('L');
    
    $totalOrdenes = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio o $where")->fetch_assoc()['t'];
    $entregadas = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio o $where AND o.estado = 'entregado'")->fetch_assoc()['t'];
    $pendientes = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio o $where AND o.estado NOT IN ('entregado', 'cancelado')")->fetch_assoc()['t'];
    $ingresos = $conn->query("SELECT COALESCE(SUM(o.costo_total), 0) as t FROM ordenes_servicio o $where")->fetch_assoc()['t'];
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(45, 8, 'Total Órdenes:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(30, 8, $totalOrdenes, 0, 1);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(45, 8, 'Entregadas:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(25, 135, 84);
    $pdf->Cell(30, 8, $entregadas, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(45, 8, 'Pendientes:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(255, 193, 7);
    $pdf->Cell(30, 8, $pendientes, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(45, 8, 'Ingresos Total:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(30, 8, 'S/ ' . number_format($ingresos, 2), 0, 1);
    
    $pdf->Ln(10);
    
    $pdf->sectionTitle('Órdenes por Estado');
    $estados = $conn->query("
        SELECT o.estado, COUNT(*) as total 
        FROM ordenes_servicio o $where 
        GROUP BY o.estado
    ");
    
    $pdf->tableHeader();
    $pdf->Cell(80, 8, 'Estado', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Porcentaje', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 10);
    $colores = [
        'recibido' => [13, 110, 253],
        'diagnostico' => [108, 117, 125],
        'reparacion' => [255, 193, 7],
        'reparado' => [25, 135, 84],
        'entregado' => [25, 135, 84],
        'cancelado' => [220, 53, 69]
    ];
    
    while ($e = $estados->fetch_assoc()) {
        $porc = round($e['total'] / $totalOrdenes * 100, 1);
        $color = $colores[$e['estado']] ?? [0, 0, 0];
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->Cell(80, 7, strtoupper($e['estado']), 1, 0, 'L');
        $pdf->Cell(40, 7, $e['total'], 1, 0, 'C');
        $pdf->Cell(40, 7, $porc . '%', 1, 1, 'C');
    }
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(10);
    $pdf->sectionTitle('Órdenes por Mes');
    
    $pdf->tableHeader();
    $pdf->Cell(40, 8, 'Mes', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(60, 8, 'Ingresos', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Ticket Prom.', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 9);
    $meses = $conn->query("
        SELECT DATE_FORMAT(o.fecha_ingreso, '%Y-%m') as mes, 
               COUNT(*) as total,
               COALESCE(SUM(o.costo_total), 0) as ingresos
        FROM ordenes_servicio o $where
        GROUP BY DATE_FORMAT(o.fecha_ingreso, '%Y-%m')
        ORDER BY mes
    ");
    
    while ($m = $meses->fetch_assoc()) {
        $promedio = $m['total'] > 0 ? $m['ingresos'] / $m['total'] : 0;
        $nombreMes = date('M Y', strtotime($m['mes'] . '-01'));
        $pdf->Cell(40, 7, $nombreMes, 1, 0, 'L');
        $pdf->Cell(40, 7, $m['total'], 1, 0, 'C');
        $pdf->Cell(60, 7, 'S/ ' . number_format($m['ingresos'], 2), 1, 0, 'R');
        $pdf->Cell(30, 7, 'S/ ' . number_format($promedio, 2), 1, 1, 'R');
    }
    
    $pdf->Ln(10);
    $pdf->sectionTitle('Top Técnicos');
    
    $pdf->tableHeader();
    $pdf->Cell(80, 8, 'Técnico', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Total', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Resueltas', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 10);
    $tecnicos = $conn->query("
        SELECT u.nombre, 
               COUNT(o.id) as total,
               SUM(CASE WHEN o.estado = 'entregado' THEN 1 ELSE 0 END) as resueltas
        FROM usuarios u
        LEFT JOIN ordenes_servicio o ON u.id = o.tecnico_id AND YEAR(o.fecha_ingreso) = $anio
        WHERE u.rol = 'tecnico'
        GROUP BY u.id
        ORDER BY total DESC
        LIMIT 10
    ");
    
    while ($t = $tecnicos->fetch_assoc()) {
        $pdf->Cell(80, 7, $t['nombre'], 1, 0, 'L');
        $pdf->Cell(40, 7, $t['total'], 1, 0, 'C');
        $pdf->Cell(40, 7, $t['resueltas'], 1, 1, 'C');
    }
    
    return $pdf;
}

function generarReporteClientes($anio) {
    $conn = getConnection();
    
    $pdf = new ReportePDF();
    $pdf->headerTitle = 'Reporte de Clientes';
    $pdf->sucursal = "Año: $anio";
    $pdf->AddPage('P');
    
    $total = $conn->query("SELECT COUNT(*) as t FROM clientes")->fetch_assoc()['t'];
    $activos = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado = 'activo'")->fetch_assoc()['t'];
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 8, 'Total Clientes:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(30, 8, $total, 0, 1);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 8, 'Activos:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(30, 8, $activos, 0, 1);
    
    $pdf->Ln(10);
    $pdf->sectionTitle('Top Clientes por Gasto');
    
    $pdf->tableHeader();
    $pdf->Cell(70, 8, 'Cliente', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Órdenes', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Total Gastado', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Última Fecha', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 9);
    $clientes = $conn->query("
        SELECT c.nombre, 
               COUNT(o.id) as ordenes,
               COALESCE(SUM(o.costo_total), 0) as gastado,
               MAX(o.fecha_ingreso) as ultimo
        FROM clientes c
        LEFT JOIN ordenes_servicio o ON c.id = o.cliente_id AND YEAR(o.fecha_ingreso) = $anio
        GROUP BY c.id
        ORDER BY gastado DESC
        LIMIT 20
    ");
    
    while ($c = $clientes->fetch_assoc()) {
        $pdf->Cell(70, 7, substr($c['nombre'], 0, 30), 1, 0, 'L');
        $pdf->Cell(30, 7, $c['ordenes'], 1, 0, 'C');
        $pdf->Cell(40, 7, 'S/ ' . number_format($c['gastado'], 2), 1, 0, 'R');
        $pdf->Cell(30, 7, $c['ultimo'] ? date('d/m/y', strtotime($c['ultimo'])) : '-', 1, 1, 'C');
    }
    
    return $pdf;
}

function generarReporteInventario($anio) {
    $conn = getConnection();
    
    $pdf = new ReportePDF();
    $pdf->headerTitle = 'Reporte de Inventario';
    $pdf->sucursal = "Año: $anio";
    $pdf->AddPage('P');
    
    $total = $conn->query("SELECT COUNT(*) as t FROM repuestos WHERE estado = 'activo'")->fetch_assoc()['t'];
    $bajoStock = $conn->query("SELECT COUNT(*) as t FROM repuestos WHERE stock <= stock_minimo AND estado = 'activo'")->fetch_assoc()['t'];
    $valor = $conn->query("SELECT COALESCE(SUM(stock * precio_venta), 0) as t FROM repuestos WHERE estado = 'activo'")->fetch_assoc()['t'];
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 8, 'Total Repuestos:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(30, 8, $total, 0, 1);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 8, 'Bajo Stock:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(220, 53, 69);
    $pdf->Cell(30, 8, $bajoStock, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 8, 'Valor Total:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(30, 8, 'S/ ' . number_format($valor, 2), 0, 1);
    
    $pdf->Ln(10);
    $pdf->sectionTitle('Repuestos con Bajo Stock');
    
    $pdf->tableHeader();
    $pdf->Cell(50, 8, 'Código', 1, 0, 'C', true);
    $pdf->Cell(70, 8, 'Nombre', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Stock', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Mínimo', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(220, 53, 69);
    $items = $conn->query("
        SELECT codigo, nombre, stock, stock_minimo
        FROM repuestos
        WHERE stock <= stock_minimo AND estado = 'activo'
        ORDER BY (stock_minimo - stock) DESC
        LIMIT 20
    ");
    
    while ($i = $items->fetch_assoc()) {
        $pdf->Cell(50, 7, $i['codigo'], 1, 0, 'L');
        $pdf->Cell(70, 7, substr($i['nombre'], 0, 25), 1, 0, 'L');
        $pdf->Cell(30, 7, $i['stock'], 1, 0, 'C');
        $pdf->Cell(30, 7, $i['stock_minimo'], 1, 1, 'C');
    }
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(10);
    $pdf->sectionTitle('Por Categoría');
    
    $pdf->tableHeader();
    $pdf->Cell(80, 8, 'Categoría', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Valor', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 9);
    $cats = $conn->query("
        SELECT categoria, COUNT(*) as total, 
               COALESCE(SUM(stock * precio_venta), 0) as valor
        FROM repuestos
        WHERE estado = 'activo'
        GROUP BY categoria
        ORDER BY total DESC
    ");
    
    while ($c = $cats->fetch_assoc()) {
        $pdf->Cell(80, 7, $c['categoria'], 1, 0, 'L');
        $pdf->Cell(50, 7, $c['total'], 1, 0, 'C');
        $pdf->Cell(50, 7, 'S/ ' . number_format($c['valor'], 2), 1, 1, 'R');
    }
    
    return $pdf;
}

function generarReporteEjecutivo($anio) {
    $conn = getConnection();
    
    $pdf = new ReportePDF();
    $pdf->headerTitle = 'Reporte Ejecutivo';
    $pdf->sucursal = "Año: $anio";
    $pdf->AddPage('P');
    
    $ordenes = $conn->query("SELECT COUNT(*) as t FROM ordenes_servicio WHERE YEAR(fecha_ingreso) = $anio")->fetch_assoc()['t'];
    $ingresos = $conn->query("SELECT COALESCE(SUM(total), 0) as t FROM facturas WHERE YEAR(fecha_emision) = $anio")->fetch_assoc()['t'];
    $clientes = $conn->query("SELECT COUNT(*) as t FROM clientes")->fetch_assoc()['t'];
    $tecnicos = $conn->query("SELECT COUNT(*) as t FROM usuarios WHERE rol = 'tecnico' AND estado = 'activo'")->fetch_assoc()['t'];
    
    $x = 20;
    $y = 40;
    $w = 80;
    $h = 40;
    
    $colors = [
        [13, 110, 253],
        [25, 135, 84],
        [255, 193, 7],
        [108, 117, 125]
    ];
    
    $stats = [
        ['Órdenes', $ordenes, 'bi bi-ticket-detailed'],
        ['Ingresos', 'S/ ' . number_format($ingresos, 0), 'bi bi-cash'],
        ['Clientes', $clientes, 'bi bi-people'],
        ['Técnicos', $tecnicos, 'bi bi-person-gear']
    ];
    
    $pdf->SetFillColor(248, 249, 250);
    $pdf->SetDrawColor(200, 200, 200);
    
    for ($i = 0; $i < 4; $i++) {
        $col = $i % 2;
        $row = floor($i / 2);
        
        $px = $x + ($col * ($w + 10));
        $py = $y + ($row * ($h + 10));
        
        $pdf->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
        $pdf->Rect($px, $py, $w, $h, 'F');
        
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY($px, $py + 5);
        $pdf->Cell($w, 6, $stats[$i][0], 0, 2, 'C');
        
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell($w, 15, $stats[$i][1], 0, 2, 'C');
    }
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(100);
    
    $pdf->sectionTitle('Resumen Mensual');
    $pdf->tableHeader();
    $pdf->Cell(40, 8, 'Mes', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Órdenes', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Ingresos', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Clientes Nuevos', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 9);
    $mensual = $conn->query("
        SELECT DATE_FORMAT(o.fecha_ingreso, '%Y-%m') as mes,
               COUNT(*) as ordenes,
               COALESCE(SUM(o.costo_total), 0) as ingresos
        FROM ordenes_servicio o
        WHERE YEAR(o.fecha_ingreso) = $anio
        GROUP BY DATE_FORMAT(o.fecha_ingreso, '%Y-%m')
        ORDER BY mes
    ");
    
    while ($m = $mensual->fetch_assoc()) {
        $nuevos = $conn->query("
            SELECT COUNT(*) as t FROM clientes 
            WHERE YEAR(fecha_registro) = $anio 
            AND DATE_FORMAT(fecha_registro, '%Y-%m') = '{$m['mes']}'
        ")->fetch_assoc()['t'];
        
        $pdf->Cell(40, 7, date('M', strtotime($m['mes'] . '-01')), 1, 0, 'L');
        $pdf->Cell(35, 7, $m['ordenes'], 1, 0, 'C');
        $pdf->Cell(45, 7, 'S/ ' . number_format($m['ingresos'], 0), 1, 0, 'R');
        $pdf->Cell(35, 7, $nuevos, 1, 1, 'C');
    }
    
    return $pdf;
}