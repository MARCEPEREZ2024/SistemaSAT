<?php

function export_to_csv($data, $filename, $headers = []) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    if (!empty($headers)) {
        fputcsv($output, $headers, ';');
    }
    
    foreach ($data as $row) {
        if (is_array($row)) {
            fputcsv($output, array_values($row), ';');
        }
    }
    
    fclose($output);
    exit();
}

function export_to_excel($data, $filename, $title = 'Export') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    $html .= '<head><meta charset="UTF-8"></head><body>';
    $html .= '<table border="1">';
    
    if (!empty($data)) {
            $html .= '<thead><tr>';
            foreach (array_keys(reset($data)) as $header) {
                $html .= '<th style="background:#f0f0f0; font-weight:bold;">' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
    }
    
    $html .= '</tbody></table></body></html>';
    
    echo $html;
    exit();
}

function export_to_json($data, $filename) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

function export_orders_to_csv($ordenes, $filename = 'ordenes') {
    $headers = ['ID', 'Código', 'Cliente', 'Equipo', 'Estado', 'Prioridad', 'Costo Total', 'Fecha Ingreso'];
    
    $data = [];
    while ($orden = $ordenes->fetch_assoc()) {
        $data[] = [
            $orden['id'],
            $orden['codigo'],
            $orden['cliente_nombre'],
            $orden['marca'] . ' ' . $orden['modelo'],
            $orden['estado'],
            $orden['prioridad'],
            $orden['costo_total'],
            $orden['fecha_ingreso']
        ];
    }
    
    export_to_csv($data, $filename, $headers);
}

function export_clients_to_csv($clientes, $filename = 'clientes') {
    $headers = ['ID', 'Nombre', 'Email', 'Teléfono', 'DNI', 'Dirección', 'Fecha Registro'];
    
    $data = [];
    while ($cliente = $clientes->fetch_assoc()) {
        $data[] = [
            $cliente['id'],
            $cliente['nombre'],
            $cliente['email'],
            $cliente['telefono'],
            $cliente['dni'],
            $cliente['direccion'],
            $cliente['fecha_registro']
        ];
    }
    
    export_to_csv($data, $filename, $headers);
}

function export_inventory_to_csv($inventario, $filename = 'inventario') {
    $headers = ['Código', 'Nombre', 'Categoría', 'Stock', 'Precio Compra', 'Precio Venta'];
    
    $data = [];
    while ($item = $inventario->fetch_assoc()) {
        $data[] = [
            $item['codigo'],
            $item['nombre'],
            $item['categoria'],
            $item['stock'],
            $item['precio_compra'],
            $item['precio_venta']
        ];
    }
    
    export_to_csv($data, $filename, $headers);
}

function download_file($filepath, $filename = '') {
    if (!file_exists($filepath)) {
        echo "Archivo no encontrado";
        return;
    }
    
    while (ob_get_level()) ob_end_clean();
    
    $filename = $filename ?: basename($filepath);
    $mime = mime_content_type($filepath);
    
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    
    readfile($filepath);
    exit();
}