<?php
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'include/funciones.php';
require_once 'include/header.php';

if (!isLoggedIn()) {
    redirect('autenticacion/login.php');
}

$page_title = 'Dashboard';
redirect('dashboard/index.php');
?>