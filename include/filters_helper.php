<?php

class Filtro {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function buscar($termino, $campos) {
        if (empty($termino)) {
            return '';
        }
        
        $termino = "%" . $this->conn->real_escape_string($termino) . "%";
        $condiciones = [];
        
        foreach ($campos as $campo) {
            $condiciones[] = "$campo LIKE '$termino'";
        }
        
        return ' AND (' . implode(' OR ', $condiciones) . ')';
    }
    
    public function estado($estados) {
        if (empty($estados)) {
            return '';
        }
        
        $lista = implode("','", array_map([$this->conn, 'real_escape_string'], $estados));
        return " AND estado IN ('$lista')";
    }
    
    public function rango_precio($min, $max, $campo = 'costo_total') {
        if ($min && $max) {
            return " AND $campo BETWEEN $min AND $max";
        }
        return '';
    }
}

function aplicar_filtros_ordenes($get) {
    $filtros = [];
    $conn = getConnection();
    
    // Estado
    if (!empty($get['estado'])) {
        $est = $conn->real_escape_string($get['estado']);
        $filtros[] = " AND o.estado = '$est'";
    }
    
    // Prioridad
    if (!empty($get['prioridad'])) {
        $pri = $conn->real_escape_string($get['prioridad']);
        $filtros[] = " AND o.prioridad = '$pri'";
    }
    
    // Técnico
    if (!empty($get['tecnico_id'])) {
        $filtros[] = " AND o.tecnico_id = " . (int)$get['tecnico_id'];
    }
    
    // Cliente
    if (!empty($get['cliente_id'])) {
        $filtros[] = " AND o.cliente_id = " . (int)$get['cliente_id'];
    }
    
    // Fecha inicio
    if (!empty($get['fecha_inicio'])) {
        $fi = date('Y-m-d', strtotime($get['fecha_inicio']));
        $filtros[] = " AND o.fecha_ingreso >= '$fi'";
    }
    
    // Fecha fin
    if (!empty($get['fecha_fin'])) {
        $ff = date('Y-m-d', strtotime($get['fecha_fin']));
        $filtros[] = " AND o.fecha_ingreso <= '$ff 23:59:59'";
    }
    
    return implode('', $filtros);
}

function generar_url_filtros($get, $nuevos = []) {
    return http_build_query(array_merge($get, $nuevos));
}

function limpiar_filtros($get, $excluir = []) {
    $limpio = [];
    $excluir = array_merge($excluir, ['page', 'export']);
    
    foreach ($get as $k => $v) {
        if (!empty($v) && !in_array($k, $excluir)) {
            $limpio[$k] = $v;
        }
    }
    
    return http_build_query($limpio);
}