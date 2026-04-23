-- =============================================================================
-- SISTEMA SAT - ARCHIVO DE RECONSTRUCCIÓN COMPLETA
-- =============================================================================
-- Este archivo contiene toda la estructura de base de datos necesaria
-- para el sistema de gestión de servicio técnico
-- 
-- Instrucciones:
-- 1. Crear nueva base de datos: CREATE DATABASE sat_db;
-- 2. Ejecutar este archivo completo en phpMyAdmin
-- 3. Verificar que todas las tablas se creen correctamente
--
-- Fecha de creación: 2026-04-22
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =============================================================================
-- TABLA: USUARIOS
-- =============================================================================
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','tecnico','ventas') DEFAULT 'tecnico',
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `comision_venta` decimal(5,2) DEFAULT '10.00',
  `comision_presentismo` decimal(5,2) DEFAULT '5.00',
  `comision_especial` decimal(5,2) DEFAULT '15.00',
  `two_factor_secret` varchar(32) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario admin por defecto (password: admin123)
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`, `telefono`, `estado`) VALUES
('Administrador', 'admin@sat.com', '$2y$12$RZsOpRcoVkxrAHjWBuaVtu/b2EBJyFHjHQCBBi/WGiV8yRH462HQ2', 'admin', '999999999', 'activo');

-- =============================================================================
-- TABLA: SUCURSALES
-- =============================================================================
DROP TABLE IF EXISTS `sucursales`;
CREATE TABLE `sucursales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `responsable_id` int DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: CLIENTES
-- =============================================================================
DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` text,
  `dni` varchar(20) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `sucursal_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: EQUIPOS
-- =============================================================================
DROP TABLE IF EXISTS `equipos`;
CREATE TABLE `equipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `serie` varchar(100) DEFAULT NULL,
  `tipo_equipo` enum('notebook','desktop','all-in-one','monitor','otro') DEFAULT 'notebook',
  `diagnostico_inicial` text,
  `passwordBIOS` varchar(50) DEFAULT NULL,
  `passwordSO` varchar(50) DEFAULT NULL,
  `accesorios` text,
  `estado_equipo` enum('bueno','regular','malo') DEFAULT 'regular',
  `fecha_ingreso` date DEFAULT NULL,
  `foto_equipo` varchar(255) DEFAULT NULL,
  `estado` enum('activo','retirado','dado_de_baja') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: ORDENES DE SERVICIO
-- =============================================================================
DROP TABLE IF EXISTS `ordenes_servicio`;
CREATE TABLE `ordenes_servicio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `equipo_id` int NOT NULL,
  `tecnico_id` int DEFAULT NULL,
  `cliente_id` int NOT NULL,
  `estado` enum('recibido','en_diagnostico','en_reparacion','esperando_repuestos','reparado','entregado','cancelado') DEFAULT 'recibido',
  `prioridad` enum('baja','normal','alta','urgente') DEFAULT 'normal',
  `diagnostico` text,
  `solucion` text,
  `observaciones` text,
  `fecha_ingreso` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_diagnostico` datetime DEFAULT NULL,
  `fecha_reparacion` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `tiempo_estimado` int DEFAULT NULL,
  `costo_diagnostico` decimal(10,2) DEFAULT '0.00',
  `costo_reparacion` decimal(10,2) DEFAULT '0.00',
  `costo_total` decimal(10,2) DEFAULT '0.00',
  `nota_cliente` text,
  `estado_orden` enum('abierta','cerrada','cancelada') DEFAULT 'abierta',
  `sucursal_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `equipo_id` (`equipo_id`),
  KEY `tecnico_id` (`tecnico_id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: ESTADOS SEGUIMIENTO
-- =============================================================================
DROP TABLE IF EXISTS `estados_seguimiento`;
CREATE TABLE `estados_seguimiento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `estado` varchar(50) NOT NULL,
  `descripcion` text,
  `tecnico_id` int DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `tecnico_id` (`tecnico_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: NOTIFICACIONES (ESQUEMA CORRECTO PARA EL CÓDIGO)
-- =============================================================================
DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text,
  `tipo` varchar(20) DEFAULT 'info',
  `usuario_id` int DEFAULT NULL,
  `orden_id` int DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `leida` tinyint DEFAULT 0,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_leida` (`leida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: MENSAJES INTERNOS (CHAT)
-- =============================================================================
DROP TABLE IF EXISTS `mensajes_internos`;
CREATE TABLE `mensajes_internos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `remitente_id` int NOT NULL,
  `destinatario_id` int DEFAULT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_remitente` (`remitente_id`),
  KEY `idx_destinatario` (`destinatario_id`),
  KEY `idx_leido` (`leido`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: FACTURAS
-- =============================================================================
DROP TABLE IF EXISTS `facturas`;
CREATE TABLE `facturas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(20) NOT NULL,
  `orden_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `igv` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `tipo_pago` enum('efectivo','tarjeta','transferencia','credito') DEFAULT 'efectivo',
  `estado_pago` enum('pendiente','pagado','cancelado') DEFAULT 'pendiente',
  `fecha_emision` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_pago` datetime DEFAULT NULL,
  `observaciones` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`),
  KEY `orden_id` (`orden_id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: DETALLE FACTURA
-- =============================================================================
DROP TABLE IF EXISTS `detalle_factura`;
CREATE TABLE `detalle_factura` (
  `id` int NOT NULL AUTO_INCREMENT,
  `factura_id` int NOT NULL,
  `descripcion` text NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: PRESUPUESTOS
-- =============================================================================
DROP TABLE IF EXISTS `presupuestos`;
CREATE TABLE `presupuestos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_presupuesto` varchar(20) NOT NULL,
  `orden_id` int DEFAULT NULL,
  `cliente_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `igv` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado','convertido') DEFAULT 'pendiente',
  `validez_dias` int DEFAULT '15',
  `observaciones` text,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_presupuesto` (`numero_presupuesto`),
  KEY `orden_id` (`orden_id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: DETALLE PRESUPUESTO
-- =============================================================================
DROP TABLE IF EXISTS `detalle_presupuesto`;
CREATE TABLE `detalle_presupuesto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `presupuesto_id` int NOT NULL,
  `descripcion` text NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `presupuesto_id` (`presupuesto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: COTIZACIONES
-- =============================================================================
DROP TABLE IF EXISTS `cotizaciones`;
CREATE TABLE `cotizaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_cotizacion` varchar(20) NOT NULL,
  `cliente_id` int NOT NULL,
  `equipo_id` int DEFAULT NULL,
  `descripcion` text,
  `validez_dias` int DEFAULT '7',
  `estado` enum('pendiente','aprobado','rechazado','convertido') DEFAULT 'pendiente',
  `observaciones` text,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_cotizacion` (`numero_cotizacion`),
  KEY `cliente_id` (`cliente_id`),
  KEY `equipo_id` (`equipo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: DETALLE COTIZACION
-- =============================================================================
DROP TABLE IF EXISTS `detalle_cotizacion`;
CREATE TABLE `detalle_cotizacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int NOT NULL,
  `descripcion` text NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cotizacion_id` (`cotizacion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: REPUESTOS (INVENTARIO)
-- =============================================================================
DROP TABLE IF EXISTS `repuestos`;
CREATE TABLE `repuestos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `categoria` varchar(50) DEFAULT NULL,
  `marca_compatible` varchar(100) DEFAULT NULL,
  `modelo_compatible` varchar(100) DEFAULT NULL,
  `stock` int DEFAULT '0',
  `stock_minimo` int DEFAULT '5',
  `precio_compra` decimal(10,2) DEFAULT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  `ubicacion` varchar(50) DEFAULT NULL,
  `estado` enum('activo','inactivo','descontinuado') DEFAULT 'activo',
  `sucursal_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: MOVIMIENTOS INVENTARIO
-- =============================================================================
DROP TABLE IF EXISTS `movimientos_inventario`;
CREATE TABLE `movimientos_inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `repuesto_id` int NOT NULL,
  `tipo` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad` int NOT NULL,
  `orden_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `nota` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `repuesto_id` (`repuesto_id`),
  KEY `orden_id` (`orden_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: REPUESTOS ORDEN
-- =============================================================================
DROP TABLE IF EXISTS `repuestos_orden`;
CREATE TABLE `repuestos_orden` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `repuesto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `repuesto_id` (`repuesto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: GARANTIAS
-- =============================================================================
DROP TABLE IF EXISTS `garantias`;
CREATE TABLE `garantias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `meses` int NOT NULL DEFAULT '3',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `descripcion` text,
  `estado` enum('activa','vencida','usada') DEFAULT 'activa',
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: AGENDA ENTREGAS
-- =============================================================================
DROP TABLE IF EXISTS `agenda_entregas`;
CREATE TABLE `agenda_entregas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `fecha_entrega` date NOT NULL,
  `hora_entrega` varchar(10) DEFAULT NULL,
  `nota` text,
  `recordatorio_enviado` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: CONFIGURACIONES
-- =============================================================================
DROP TABLE IF EXISTS `configuraciones`;
CREATE TABLE `configuraciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) NOT NULL,
  `valor` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configuraciones iniciales
INSERT INTO `configuraciones` (`clave`, `valor`) VALUES
('empresa_nombre', 'Servicio Técnico SAT'),
('empresa_direccion', 'Av. Principal 123'),
('empresa_telefono', '999-999-999'),
('empresa_ruc', '20123456789'),
('igv_porcentaje', '18'),
('moneda', 'S/');

-- =============================================================================
-- TABLA: AUDIT LOG
-- =============================================================================
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `usuario_nombre` varchar(100) DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `tabla` varchar(50) DEFAULT NULL,
  `registro_id` int DEFAULT NULL,
  `datos_anteriores` text,
  `datos_nuevos` text,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_accion` (`accion`),
  KEY `idx_tabla` (`tabla`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: ACTIVIDADES
-- =============================================================================
DROP TABLE IF EXISTS `actividades`;
CREATE TABLE `actividades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `entidad` varchar(50) DEFAULT NULL,
  `entidad_id` int DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `detalles` text,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: LOGS SEGURIDAD
-- =============================================================================
DROP TABLE IF EXISTS `logs_seguridad`;
CREATE TABLE `logs_seguridad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT '0',
  `evento` varchar(100) NOT NULL,
  `detalles` text,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- TABLA: CONFIG SEGURIDAD
-- =============================================================================
DROP TABLE IF EXISTS `config_seguridad`;
CREATE TABLE `config_seguridad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) NOT NULL,
  `valor` text,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`),
  KEY `idx_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `config_seguridad` (`clave`, `valor`, `descripcion`) VALUES
('max_login_attempts', '5', 'Máximo intentos de login'),
('lockout_time', '300', 'Tiempo de bloqueo en segundos'),
('session_timeout', '1800', 'Timeout de sesión en segundos'),
('min_password_length', '8', 'Longitud mínima de contraseña'),
('enable_2fa', '0', 'Habilitar autenticación de dos factores'),
('require_password_complex', '1', 'Requerir contraseña compleja');

-- =============================================================================
-- VISTAS
-- =============================================================================
DROP VIEW IF EXISTS `vista_facturas_mes`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_facturas_mes` AS
SELECT month(`facturas`.`fecha_emision`) AS `mes`, sum(`facturas`.`total`) AS `total` 
FROM `facturas` 
WHERE (year(`facturas`.`fecha_emision`) = year(curdate())) 
GROUP BY month(`facturas`.`fecha_emision`);

DROP VIEW IF EXISTS `vista_ordenes_estado`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_ordenes_estado` AS
SELECT `ordenes_servicio`.`estado` AS `estado`, count(0) AS `total` 
FROM `ordenes_servicio` 
WHERE (`ordenes_servicio`.`estado_orden` = 'abierta') 
GROUP BY `ordenes_servicio`.`estado`;

COMMIT;

-- =============================================================================
-- NOTAS FINALES
-- =============================================================================
-- 1. El usuario administrador se crea con:
--    Email: admin@sat.com
--    Password: admin123
--
-- 2. Asegúrate de ejecutar: composer install
--    En la raíz del proyecto para instalar PHPMailer
--
-- 3. Configura el archivo config/database.php con tus credenciales
--
-- 4. Para WAMP: Asegúrate de tener la extensión mysqli habilitada
-- =============================================================================