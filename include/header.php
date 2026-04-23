<?php 
require_once __DIR__ . '/../config/config.php';

if (!isset($page_title)) $page_title = 'Sistema SAT'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Sistema SAT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/improved.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/animations.css">
</head>
<body>
<?php if (isLoggedIn()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>dashboard/index.php">
            <i class="bi bi-tools"></i> Sistema SAT
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isAdmin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear"></i> Administración
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>usuarios/listar.php"><i class="bi bi-people"></i> Gestión de Usuarios</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracion/sucursales.php"><i class="bi bi-building"></i> Sucursales</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracion/whatsapp.php"><i class="bi bi-whatsapp"></i> WhatsApp</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracion/backup.php"><i class="bi bi-hdd-drive"></i> Backup</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracion/audit.php"><i class="bi bi-journal-text"></i> Audit Trail</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracion/email.php"><i class="bi bi-envelope-at"></i> Configuración Email</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>reportes/comisiones.php"><i class="bi bi-cash"></i> Comisiones</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>reportes/index.php"><i class="bi bi-bar-chart"></i> Reportes</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" id="notifDropdown">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; display: none;" id="notifBadge">
                            0
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header d-flex justify-content-between">
                            <span>Notificaciones</span>
                            <a href="#" class="small" onclick="markAllRead()">Marcar todo leído</a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <div id="notifList">
                            <div class="text-center text-muted p-3">Sin notificaciones</div>
                        </div>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= BASE_URL ?>chat/index.php">
                        <i class="bi bi-chat-dots"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info" style="font-size: 0.65rem; display: none;" id="chatBadge">
                            0
                        </span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= $_SESSION['nombre'] ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>autenticacion/perfil.php"><i class="bi bi-person"></i> Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>autenticacion/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>dashboard/index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>ordenes/listar.php">
                            <i class="bi bi-ticket-detailed"></i> Órdenes de Servicio
                        </a>
                    </li>
<li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>clientes/listar.php">
                            <i class="bi bi-people"></i> Clientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>facturacion/listar.php">
                            <i class="bi bi-receipt"></i> Facturación
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>presupuestos/listar.php">
                            <i class="bi bi-file-earmark-text"></i> Presupuestos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>cotizaciones/listar.php">
                            <i class="bi bi-file-earmark-ruled"></i> Cotizaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>agenda/index.php">
                            <i class="bi bi-calendar-check"></i> Agenda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>equipos/historial.php">
                            <i class="bi bi-journal-bookmark"></i> Historial por Serie
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>inventario/listar.php">
                            <i class="bi bi-box-seam"></i> Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>garantias/listar.php">
                            <i class="bi bi-shield-check"></i> Garantías
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>chat/index.php">
                            <i class="bi bi-chat-dots"></i> Chat Interno
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-menu-button-wide"></i> Utilidades
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>buscar/index.php"><i class="bi bi-search"></i> Buscar</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>equipos/historial.php"><i class="bi bi-journal-bookmark"></i> Historial por Serie</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>exportar/index.php"><i class="bi bi-download"></i> Exportar</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
<?php endif; ?>