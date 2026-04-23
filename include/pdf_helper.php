<?php

class PDFGenerator {
    private $config;
    
    public function __construct() {
        $this->loadConfig();
    }
    
    private function loadConfig() {
        $conn = getConnection();
        $result = $conn->query("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'empresa_%' OR clave = 'igv_porcentaje'");
        $this->config = [];
        while ($row = $result->fetch_assoc()) {
            $this->config[$row['clave']] = $row['valor'];
        }
    }
    
    public function getHeader() {
        $nombre = $this->config['empresa_nombre'] ?? 'Sistema SAT';
        $direccion = $this->config['empresa_direccion'] ?? '';
        $telefono = $this->config['empresa_telefono'] ?? '';
        $ruc = $this->config['empresa_ruc'] ?? '';
        
        return [
            'nombre' => $nombre,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'ruc' => $ruc
        ];
    }
    
    public function presupuesto($presupuesto_id) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("
            SELECT p.*, c.nombre as cliente_nombre, c.email as cliente_email, 
                   c.direccion as cliente_direccion, c.telefono as cliente_telefono,
                   c.dni as cliente_dni
            FROM presupuestos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $presupuesto_id);
        $stmt->execute();
        $pres = $stmt->get_result()->fetch_assoc();
        
        if (!$pres) return false;
        
        $item = $conn->prepare("SELECT * FROM detalle_presupuesto WHERE presupuesto_id = ?");
        $item->bind_param("i", $presupuesto_id);
        $item->execute();
        $items = $item->get_result();
        
        // Generar HTML simple
        $header = $this->getHeader();
        $igv = $this->config['igv_porcentaje'] ?? 18;
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Presupuesto ' . $pres['codigo'] . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .empresa { font-size: 18px; font-weight: bold; }
        .doc-title { font-size: 24px; text-align: right; }
        .info { margin-bottom: 20px; }
        .info p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .totals { text-align: right; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="empresa">
            ' . $header['nombre'] . '<br>
            <small>RUC: ' . $header['ruc'] . '<br>
            ' . $header['direccion'] . '<br>
            Telf: ' . $header['telefono'] . '</small>
        </div>
        <div class="doc-title">PRESUPUESTO<br><small>' . $pres['codigo'] . '</small></div>
    </div>
    
    <div class="info">
        <p><strong>Cliente:</strong> ' . htmlspecialchars($pres['cliente_nombre']) . '</p>
        <p><strong>DNI/RUC:</strong> ' . htmlspecialchars($pres['cliente_dni'] ?? '-') . '</p>
        <p><strong>Dirección:</strong> ' . htmlspecialchars($pres['cliente_direccion'] ?? '-') . '</p>
        <p><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($pres['fecha'])) . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width:50%">Descripción</th>
                <th style="width:15%">Cantidad</th>
                <th style="width:17%">P. Unitario</th>
                <th style="width:18%">Importe</th>
            </tr>
        </thead>
        <tbody>';
        
        $subtotal = 0;
        while ($it = $items->fetch_assoc()) {
            $importe = $it['cantidad'] * $it['precio_unitario'];
            $subtotal += $importe;
            $html .= '<tr>
                <td>' . htmlspecialchars($it['descripcion']) . '</td>
                <td>' . $it['cantidad'] . '</td>
                <td>S/ ' . number_format($it['precio_unitario'], 2) . '</td>
                <td>S/ ' . number_format($importe, 2) . '</td>
            </tr>';
        }
        
        $igv_monto = $subtotal * ($igv / 100);
        $total = $subtotal + $igv_monto;
        
        $html .= '</tbody>
    </table>
    
    <div class="totals">
        <p>Subtotal: <strong>S/ ' . number_format($subtotal, 2) . '</strong></p>
        <p>IGV (' . $igv . '%): <strong>S/ ' . number_format($igv_monto, 2) . '</strong></p>
        <p>TOTAL: <strong>S/ ' . number_format($total, 2) . '</strong></p>
    </div>
    
    <div class="footer">
        <p>Válido por ' . ($pres['validez_dias'] ?? 15) . ' días</p>
        <p>' . nl2br(htmlspecialchars($pres['observaciones'] ?? '')) . '</p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    public function factura($factura_id) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("
            SELECT f.*, c.nombre as cliente_nombre, c.email as cliente_email, 
                   c.direccion as cliente_direccion, c.telefono as cliente_telefono,
                   c.dni as cliente_dni
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE f.id = ?
        ");
        $stmt->bind_param("i", $factura_id);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        
        if (!$factura) return false;
        
        $item = $conn->prepare("SELECT * FROM detalle_factura WHERE factura_id = ?");
        $item->bind_param("i", $factura_id);
        $item->execute();
        $items = $item->get_result();
        
        $header = $this->getHeader();
        $igv = $this->config['igv_porcentaje'] ?? 18;
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura ' . $factura['numero_factura'] . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .empresa { font-size: 18px; font-weight: bold; }
        .doc-title { font-size: 24px; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .totals { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="empresa">
            ' . $header['nombre'] . '<br>
            <small>RUC: ' . $header['ruc'] . '</small>
        </div>
        <div class="doc-title">FACTURA<br><small>' . $factura['numero_factura'] . '</small></div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>P. Unit.</th>
                <th>Importe</th>
            </tr>
        </thead>
        <tbody>';
        
        while ($it = $items->fetch_assoc()) {
            $html .= '<tr>
                <td>' . htmlspecialchars($it['descripcion']) . '</td>
                <td>' . $it['cantidad'] . '</td>
                <td>S/ ' . number_format($it['precio_unitario'], 2) . '</td>
                <td>S/ ' . number_format($it['importe'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
    
    <div class="totals">
        <p>Subtotal: S/ ' . number_format($factura['subtotal'], 2) . '</p>
        <p>IGV: S/ ' . number_format($factura['igv'], 2) . '</p>
        <p><strong>TOTAL: S/ ' . number_format($factura['total'], 2) . '</strong></p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    public function orden($orden_id) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("
            SELECT o.*, c.nombre as cliente_nombre, c.email as cliente_email, 
                   c.telefono as cliente_telefono, e.marca, e.modelo, e.serie,
                   u.nombre as tecnico_nombre
            FROM ordenes_servicio o
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN equipos e ON o.equipo_id = e.id
            LEFT JOIN usuarios u ON o.tecnico_id = u.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $orden_id);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        
        if (!$orden) return false;
        
        $header = $this->getHeader();
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Orden ' . $orden['codigo'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px; }
        .bg { background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>ORDEN DE SERVICIO</h2>
    <p><strong>Código:</strong> ' . $orden['codigo'] . '</p>
    <p><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($orden['fecha_ingreso'])) . '</p>
    
    <table>
        <tr><td class="bg"><strong>Cliente:</strong></td><td>' . htmlspecialchars($orden['cliente_nombre']) . '</td></tr>
        <tr><td class="bg"><strong>Teléfono:</strong></td><td>' . htmlspecialchars($orden['cliente_telefono']) . '</td></tr>
        <tr><td class="bg"><strong>Equipo:</strong></td><td>' . htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']) . '</td></tr>
        <tr><td class="bg"><strong>Serie:</strong></td><td>' . htmlspecialchars($orden['serie'] ?? '-') . '</td></tr>
        <tr><td class="bg"><strong>Estado:</strong></td><td>' . strtoupper($orden['estado']) . '</td></tr>
        <tr><td class="bg"><strong>Técnico:</strong></td><td>' . htmlspecialchars($orden['tecnico_nombre'] ?? '-') . '</td></tr>
    </table>
    
    <p><strong>Diagnóstico:</strong></p>
    <p>' . nl2br(htmlspecialchars($orden['diagnostico'] ?? '-')) . '</p>
    
    <p><strong>Solución:</strong></p>
    <p>' . nl2br(htmlspecialchars($orden['solucion'] ?? '-')) . '</p>
</body>
</html>';
        
        return $html;
    }
    
    public function download($html, $filename = 'documento') {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.html"');
        echo $html;
    }
}

function generar_pdf_presupuesto($id) {
    $pdf = new PDFGenerator();
    $html = $pdf->presupuesto($id);
    $pdf->download($html, 'presupuesto_' . $id);
}

function generar_pdf_factura($id) {
    $pdf = new PDFGenerator();
    $html = $pdf->factura($id);
    $pdf->download($html, 'factura_' . $id);
}

function generar_pdf_orden($id) {
    $pdf = new PDFGenerator();
    $html = $pdf->orden($id);
    $pdf->download($html, 'orden_' . $id);
}