<?php

function require_login() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "autenticacion/login.php");
        exit();
    }
}

function require_role($role) {
    require_login();
    
    if ($_SESSION['rol'] !== $role && $_SESSION['rol'] !== 'admin') {
        header("Location: " . BASE_URL . "dashboard/index.php?error=Permisos insuficientes");
        exit();
    }
}

function is_admin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function is_tecnico() {
    return isset($_SESSION['rol']) && ($_SESSION['rol'] === 'tecnico' || $_SESSION['rol'] === 'admin');
}

function is_ventas() {
    return isset($_SESSION['rol']) && ($_SESSION['rol'] === 'ventas' || $_SESSION['rol'] === 'admin');
}

function force_password_change() {
    if (!isset($_SESSION['force_password_change'])) {
        return false;
    }
    return $_SESSION['force_password_change'] === true;
}

function check_permission($permission) {
    $permissions = [
        'admin' => ['*'],
        'tecnico' => ['ordenes.*', 'equipos.*', 'inventario.view', 'clientes.view'],
        'ventas' => ['clientes.*', 'ordenes.view', 'facturacion.*', 'presupuestos.*']
    ];
    
    $user_role = $_SESSION['rol'] ?? 'guest';
    
    if (!isset($permissions[$user_role])) {
        return false;
    }
    
    if (in_array('*', $permissions[$user_role])) {
        return true;
    }
    
    return in_array($permission, $permissions[$user_role]);
}