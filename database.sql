-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 23-04-2026 a las 03:27:27
-- Versión del servidor: 8.4.7
-- Versión de PHP: 8.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sat_db`
--
CREATE DATABASE IF NOT EXISTS `sat_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sat_db`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

DROP TABLE IF EXISTS `actividades`;
CREATE TABLE IF NOT EXISTS `actividades` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agenda_entregas`
--

DROP TABLE IF EXISTS `agenda_entregas`;
CREATE TABLE IF NOT EXISTS `agenda_entregas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `fecha_entrega` date NOT NULL,
  `hora_entrega` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nota` text COLLATE utf8mb4_unicode_ci,
  `recordatorio_enviado` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `agenda_entregas`
--

INSERT INTO `agenda_entregas` (`id`, `orden_id`, `fecha_entrega`, `hora_entrega`, `nota`, `recordatorio_enviado`) VALUES
(1, 12, '2026-04-22', '10:09', '', 0),
(2, 10, '2026-04-25', '', '', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE IF NOT EXISTS `audit_log` (
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `audit_log`
--

INSERT INTO `audit_log` (`id`, `usuario_id`, `usuario_nombre`, `accion`, `tabla`, `registro_id`, `datos_anteriores`, `datos_nuevos`, `ip`, `user_agent`, `fecha`) VALUES
(1, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 05:11:26'),
(2, 1, 'Administrador', 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 05:12:07'),
(3, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 05:12:09'),
(4, 1, 'Administrador', 'actualizar', 'clientes', 4, '{\"id\":4,\"nombre\":\"fabian\",\"email\":\"marcelo.perez.b22@gmail.com\",\"telefono\":\"091503101\",\"direccion\":\"Dr. E. Sineiro S\\/N y Av Batlle Oro\\u00f1ez, San Ramon\",\"dni\":\"\",\"fecha_registro\":\"2026-04-18 01:27:38\",\"estado\":\"activo\",\"sucursal_id\":null}', '{\"nombre\":\"fabian\",\"email\":\"marcelo.perez.b22@gmail.com\",\"telefono\":\"091503101\",\"dni\":\"\",\"direccion\":\"Dr. E. Sineiro S\\/N y Av Batlle Oro\\u00f1ez, San Ramon\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 05:18:46'),
(5, 1, 'Administrador', 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 05:23:28'),
(6, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 05:30:28'),
(7, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 21:45:03'),
(8, 1, 'Administrador', 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 21:45:28'),
(9, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 21:45:33'),
(10, 1, 'Administrador', 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 22:46:52'),
(11, 6, 'benjamin', 'login_exitoso', 'usuarios', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 22:47:36'),
(12, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-04-22 22:48:36'),
(13, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 23:26:11'),
(14, 1, 'Administrador', 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 23:40:46'),
(15, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 23:40:48'),
(16, 6, 'benjamin', 'login_exitoso', 'usuarios', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-23 00:24:54'),
(17, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-23 02:27:53'),
(18, 1, 'Administrador', 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-23 02:37:02'),
(19, 1, 'Administrador', 'login_exitoso', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-23 02:47:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `dni` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `sucursal_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `email`, `telefono`, `direccion`, `dni`, `fecha_registro`, `estado`, `sucursal_id`) VALUES
(1, 'Marcelo', 'marcelo.perez.b22@gmail.com', '091503101', 'Dr. E. Sineiro S/N y Av Batlle Oroñez, San Ramon', '', '2026-04-18 01:54:57', 'activo', NULL),
(2, 'Marcelo', 'marcelo.perez.b22@gmail.com', '091503101', 'Dr. E. Sineiro S/N y Av Batlle Oroñez, San Ramon', '', '2026-04-18 01:57:13', 'activo', NULL),
(3, 'Maarcelo', 'marcelo.perez.b22@gmail.com', '091503101', 'Dr. E. Sineiro S/N y Av Batlle Oroñez, San Ramon', '', '2026-04-18 01:57:52', 'activo', NULL),
(4, 'fabian', 'marcelo.perez.b22@gmail.com', '091503101', 'Dr. E. Sineiro S/N y Av Batlle Oroñez, San Ramon', '', '2026-04-18 04:27:38', 'activo', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

DROP TABLE IF EXISTS `configuraciones`;
CREATE TABLE IF NOT EXISTS `configuraciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=MyISAM AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuraciones`
--

INSERT INTO `configuraciones` (`id`, `clave`, `valor`) VALUES
(1, 'empresa_nombre', 'Servicio Técnico SAT'),
(2, 'empresa_direccion', 'Av. Principal 123'),
(3, 'empresa_telefono', '999-999-999'),
(4, 'empresa_ruc', '20123456789'),
(5, 'igv_porcentaje', '18'),
(6, 'moneda', 'S/'),
(133, 'smtp_from_name', 'Servicio Técnico SAT'),
(132, 'smtp_from_email', 'marcelo.perez.b22@gmail.com'),
(13, 'backup_config', '{\"auto_backup\":false,\"frecuencia\":\"diario\",\"hora\":\"02:00\",\"retener\":7,\"email_notif\":false}'),
(14, 'whatsapp_provider', 'wppconnect'),
(15, 'wppconnect_url', 'http://localhost:8080/sendMessage'),
(130, 'smtp_user', 'marcelo.perez.b22@gmail.com'),
(131, 'smtp_pass', 'nsml gjbb dnge xesy'),
(128, 'smtp_host', 'smtp.gmail.com'),
(129, 'smtp_port', '587'),
(134, 'smtp_secure', 'tls');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_seguridad`
--

DROP TABLE IF EXISTS `config_seguridad`;
CREATE TABLE IF NOT EXISTS `config_seguridad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) NOT NULL,
  `valor` text,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`),
  KEY `idx_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `config_seguridad`
--

INSERT INTO `config_seguridad` (`id`, `clave`, `valor`, `descripcion`) VALUES
(1, 'max_login_attempts', '5', 'Máximo intentos de login'),
(2, 'lockout_time', '300', 'Tiempo de bloqueo en segundos'),
(3, 'session_timeout', '1800', 'Timeout de sesión en segundos'),
(4, 'min_password_length', '8', 'Longitud mínima de contraseña'),
(5, 'enable_2fa', '0', 'Habilitar autenticación de dos factores'),
(6, 'require_password_complex', '1', 'Requerir contraseña compleja');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

DROP TABLE IF EXISTS `cotizaciones`;
CREATE TABLE IF NOT EXISTS `cotizaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_cotizacion` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int NOT NULL,
  `equipo_id` int DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `validez_dias` int DEFAULT '7',
  `estado` enum('pendiente','aprobado','rechazado','convertido') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_cotizacion` (`numero_cotizacion`),
  KEY `cliente_id` (`cliente_id`),
  KEY `equipo_id` (`equipo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_cotizacion`
--

DROP TABLE IF EXISTS `detalle_cotizacion`;
CREATE TABLE IF NOT EXISTS `detalle_cotizacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cotizacion_id` (`cotizacion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_factura`
--

DROP TABLE IF EXISTS `detalle_factura`;
CREATE TABLE IF NOT EXISTS `detalle_factura` (
  `id` int NOT NULL AUTO_INCREMENT,
  `factura_id` int NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_factura`
--

INSERT INTO `detalle_factura` (`id`, `factura_id`, `descripcion`, `cantidad`, `precio_unitario`, `importe`) VALUES
(18, 15, 'Servicio técnico', 1, 10000.00, 10000.00),
(17, 14, 'Servicio técnico', 1, 2222.00, 2222.00),
(16, 13, 'Servicio técnico', 1, 2222.00, 2222.00),
(15, 12, 'Servicio técnico', 1, 7474.00, 7474.00),
(14, 11, 'Servicio técnico', 1, 2222.00, 2222.00),
(13, 10, 'solicita presupuesto', 1, 52222.00, 52222.00),
(12, 9, 'solicita presupuesto', 1, 1222.00, 1222.00),
(11, 6, 'solicita presupuesto', 1, 500.00, 500.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_presupuesto`
--

DROP TABLE IF EXISTS `detalle_presupuesto`;
CREATE TABLE IF NOT EXISTS `detalle_presupuesto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `presupuesto_id` int NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `presupuesto_id` (`presupuesto_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_presupuesto`
--

INSERT INTO `detalle_presupuesto` (`id`, `presupuesto_id`, `descripcion`, `cantidad`, `precio_unitario`, `importe`) VALUES
(4, 4, 'solicita presupuesto', 1, 1222.00, 1222.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

DROP TABLE IF EXISTS `equipos`;
CREATE TABLE IF NOT EXISTS `equipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `marca` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_equipo` enum('notebook','desktop','all-in-one','monitor','otro') COLLATE utf8mb4_unicode_ci DEFAULT 'notebook',
  `diagnostico_inicial` text COLLATE utf8mb4_unicode_ci,
  `passwordBIOS` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passwordSO` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accesorios` text COLLATE utf8mb4_unicode_ci,
  `estado_equipo` enum('bueno','regular','malo') COLLATE utf8mb4_unicode_ci DEFAULT 'regular',
  `fecha_ingreso` date DEFAULT NULL,
  `foto_equipo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','retirado','dado_de_baja') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `equipos`
--

INSERT INTO `equipos` (`id`, `cliente_id`, `marca`, `modelo`, `serie`, `tipo_equipo`, `diagnostico_inicial`, `passwordBIOS`, `passwordSO`, `accesorios`, `estado_equipo`, `fecha_ingreso`, `foto_equipo`, `estado`) VALUES
(1, 3, 'sony', 'dsgsd', '123', 'notebook', 'aaaaaaaaa', '123', '123456', 'AAAAAAA', 'regular', '2026-04-18', NULL, 'activo'),
(2, 2, 'sony', 'opr', '123', 'monitor', '', '123', '', '', 'malo', '2026-04-18', NULL, 'activo'),
(3, 2, 'sony', 'dsgsd', '123', 'notebook', '', '123', '123456', '', 'regular', '2026-04-18', NULL, 'activo'),
(4, 3, 'hp', 'opr', '123', 'notebook', '', '123', '', '', 'bueno', '2026-04-18', NULL, 'activo'),
(5, 4, 'hp', 'dsgsd', '123', 'desktop', '', '123', '', '', 'bueno', '2026-04-18', NULL, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_seguimiento`
--

DROP TABLE IF EXISTS `estados_seguimiento`;
CREATE TABLE IF NOT EXISTS `estados_seguimiento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `estado` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `tecnico_id` int DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `tecnico_id` (`tecnico_id`)
) ENGINE=MyISAM AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estados_seguimiento`
--

INSERT INTO `estados_seguimiento` (`id`, `orden_id`, `estado`, `descripcion`, `tecnico_id`, `fecha`) VALUES
(1, 1, 'recibido', 'Orden creada', 1, '2026-04-17 23:28:39'),
(2, 2, 'recibido', 'Orden creada', 1, '2026-04-18 00:11:02'),
(3, 2, 'reparado', '', 1, '2026-04-18 00:11:47'),
(4, 1, 'esperando_repuestos', '', 1, '2026-04-18 00:13:33'),
(5, 1, 'reparado', '', 1, '2026-04-18 00:16:11'),
(6, 3, 'recibido', 'Orden creada', 1, '2026-04-18 01:19:21'),
(7, 4, 'recibido', 'Orden creada', 1, '2026-04-18 01:22:56'),
(8, 5, 'recibido', 'Orden creada', 1, '2026-04-18 01:23:14'),
(9, 6, 'recibido', 'Orden creada', 1, '2026-04-18 01:24:38'),
(10, 7, 'recibido', 'Orden creada', 1, '2026-04-18 01:24:40'),
(11, 8, 'recibido', 'Orden creada', 1, '2026-04-18 01:26:26'),
(12, 8, 'en_reparacion', '', 1, '2026-04-18 01:26:44'),
(13, 9, 'recibido', 'Orden creada', 1, '2026-04-18 01:26:58'),
(14, 8, 'reparado', '', 1, '2026-04-18 01:29:18'),
(15, 10, 'recibido', 'Orden creada', 1, '2026-04-18 01:29:53'),
(16, 10, 'reparado', '', 1, '2026-04-18 01:30:42'),
(17, 9, 'reparado', '', 1, '2026-04-18 02:33:42'),
(18, 7, 'entregado', '', 1, '2026-04-18 02:57:56'),
(19, 6, 'entregado', '', 1, '2026-04-18 02:59:43'),
(20, 3, 'entregado', '', 1, '2026-04-18 03:01:01'),
(21, 2, 'entregado', '', 1, '2026-04-18 03:01:36'),
(22, 12, 'recibido', 'Orden creada', 1, '2026-04-18 11:52:14'),
(23, 12, 'reparado', '', 1, '2026-04-18 11:52:42'),
(24, 1, 'entregado', '', 1, '2026-04-21 22:49:07'),
(25, 8, 'reparado', '', 1, '2026-04-21 22:49:44'),
(26, 7, 'reparado', '', 1, '2026-04-21 22:50:07'),
(27, 2, 'reparado', '', 1, '2026-04-21 22:50:44'),
(28, 12, 'entregado', '', 1, '2026-04-22 00:52:26'),
(29, 4, 'reparado', '', 6, '2026-04-22 21:25:14'),
(30, 10, 'en_reparacion', '', 6, '2026-04-22 21:31:46'),
(31, 10, 'en_reparacion', '', 6, '2026-04-22 21:31:50'),
(32, 8, 'esperando_repuestos', '', 1, '2026-04-22 23:51:17'),
(33, 8, 'esperando_repuestos', '', 1, '2026-04-22 23:51:19'),
(34, 8, 'esperando_repuestos', '', 1, '2026-04-22 23:51:22'),
(35, 8, 'esperando_repuestos', '', 1, '2026-04-22 23:51:24'),
(36, 8, 'esperando_repuestos', '', 1, '2026-04-22 23:53:17'),
(37, 8, 'esperando_repuestos', '', 1, '2026-04-22 23:53:28'),
(38, 8, 'esperando_repuestos', '', 1, '2026-04-22 23:53:45'),
(39, 8, 'esperando_repuestos', '', 1, '2026-04-22 23:53:52'),
(40, 8, 'reparado', '', 1, '2026-04-22 23:54:06'),
(41, 8, 'reparado', '', 1, '2026-04-22 23:55:55'),
(42, 2, 'en_diagnostico', '', 1, '2026-04-22 23:56:26'),
(43, 2, 'en_diagnostico', '', 1, '2026-04-22 23:57:14'),
(44, 2, 'en_diagnostico', '', 1, '2026-04-22 23:57:36'),
(45, 2, 'en_diagnostico', '', 1, '2026-04-22 23:57:43'),
(46, 2, 'en_reparacion', '', 1, '2026-04-22 23:58:13'),
(47, 5, 'en_reparacion', '', 1, '2026-04-23 00:24:08'),
(48, 5, 'reparado', '', 1, '2026-04-23 00:25:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

DROP TABLE IF EXISTS `facturas`;
CREATE TABLE IF NOT EXISTS `facturas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `igv` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `tipo_pago` enum('efectivo','tarjeta','transferencia','credito') COLLATE utf8mb4_unicode_ci DEFAULT 'efectivo',
  `estado_pago` enum('pendiente','pagado','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `fecha_emision` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_pago` datetime DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`),
  KEY `orden_id` (`orden_id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `numero_factura`, `orden_id`, `cliente_id`, `subtotal`, `igv`, `total`, `tipo_pago`, `estado_pago`, `fecha_emision`, `fecha_pago`, `observaciones`) VALUES
(7, 'F-2026-000001', 10, 4, 500.00, 90.00, 590.00, 'credito', 'pendiente', '2026-04-18 02:46:55', NULL, 'Presupuesto: P-2026-000001'),
(8, 'F-2026-000002', 9, 3, 200.00, 36.00, 236.00, 'credito', 'pendiente', '2026-04-18 02:51:06', NULL, 'Presupuesto: P-2026-000003'),
(9, 'F-2026-000003', 12, 4, 1222.00, 219.96, 1441.96, 'credito', 'pagado', '2026-04-18 11:53:39', '2026-04-18 11:53:47', 'Presupuesto: P-2026-000004'),
(10, 'F-2026-000004', 0, 3, 52222.00, 9399.96, 61621.96, 'efectivo', 'pendiente', '2026-04-23 00:03:56', NULL, ''),
(11, 'F-2026-000005', 7, 2, 2222.00, 399.96, 2621.96, 'efectivo', 'pendiente', '2026-04-23 00:11:15', NULL, ''),
(12, 'F-2026-000006', 4, 2, 7474.00, 1345.32, 8819.32, 'efectivo', 'pendiente', '2026-04-23 00:18:07', NULL, ''),
(13, 'F-2026-000007', 7, 2, 2222.00, 399.96, 2621.96, 'efectivo', 'pendiente', '2026-04-23 00:18:16', NULL, ''),
(14, 'F-2026-000008', 7, 2, 2222.00, 399.96, 2621.96, 'efectivo', 'pendiente', '2026-04-23 00:21:37', NULL, ''),
(15, 'F-2026-000009', 8, 2, 10000.00, 1800.00, 11800.00, 'efectivo', 'pagado', '2026-04-23 00:21:53', '2026-04-23 00:26:56', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `garantias`
--

DROP TABLE IF EXISTS `garantias`;
CREATE TABLE IF NOT EXISTS `garantias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `meses` int NOT NULL DEFAULT '3',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('activa','vencida','usada') COLLATE utf8mb4_unicode_ci DEFAULT 'activa',
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `garantias`
--

INSERT INTO `garantias` (`id`, `orden_id`, `meses`, `fecha_inicio`, `fecha_fin`, `descripcion`, `estado`) VALUES
(1, 7, 6, '2026-04-18', '2026-10-18', '', 'activa'),
(2, 6, 12, '2026-04-18', '2027-04-18', '', 'activa'),
(3, 3, 1, '2026-04-18', '2026-05-18', '', 'activa'),
(4, 2, 1, '2026-04-18', '2026-05-18', '', 'activa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_seguridad`
--

DROP TABLE IF EXISTS `logs_seguridad`;
CREATE TABLE IF NOT EXISTS `logs_seguridad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT '0',
  `evento` varchar(100) NOT NULL,
  `detalles` text,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_internos`
--

DROP TABLE IF EXISTS `mensajes_internos`;
CREATE TABLE IF NOT EXISTS `mensajes_internos` (
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
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `mensajes_internos`
--

INSERT INTO `mensajes_internos` (`id`, `remitente_id`, `destinatario_id`, `mensaje`, `leido`, `created_at`) VALUES
(1, 1, 6, 'ljl', 1, '2026-04-23 00:48:37'),
(2, 6, 1, 'yykki', 1, '2026-04-23 00:49:57'),
(3, 6, 1, 'lylo8ylo8lo8y', 1, '2026-04-23 00:50:34'),
(4, 6, 1, 'lylo8ylo8lo8y', 1, '2026-04-23 00:56:20'),
(5, 6, 1, 'lylo8ylo8lo8y', 1, '2026-04-23 00:56:31'),
(6, 6, 1, 'lylo8ylo8lo8y', 1, '2026-04-23 01:01:10'),
(7, 6, 1, 'lylo8ylo8lo8y', 1, '2026-04-23 01:01:18'),
(8, 6, 1, 'lylo8ylo8lo8y', 1, '2026-04-23 01:01:43'),
(9, 6, 1, 'lylo8ylo8lo8y', 1, '2026-04-23 01:01:46'),
(10, 6, 1, 'yykki', 1, '2026-04-23 01:01:50'),
(11, 6, 1, 'yykki', 1, '2026-04-23 01:02:18'),
(12, 6, 1, 'yykki', 1, '2026-04-23 01:02:24'),
(13, 6, 1, 'lylyll', 1, '2026-04-23 01:02:40'),
(14, 6, 1, 'lylyll', 1, '2026-04-23 01:02:44'),
(15, 6, 1, 'lylyll', 1, '2026-04-23 01:02:46'),
(16, 6, 1, 'lylyll', 1, '2026-04-23 01:03:00'),
(17, 6, 1, 'lylyll', 1, '2026-04-23 01:03:02'),
(18, 1, 6, 'y8o8oy', 1, '2026-04-23 01:03:12'),
(19, 6, 1, 'lylyll', 1, '2026-04-23 01:03:55'),
(20, 1, 6, 'ki7tik7ki', 1, '2026-04-23 01:10:24'),
(21, 6, 1, 'lylyll', 1, '2026-04-23 01:10:28'),
(22, 6, 1, 'oglogolo', 1, '2026-04-23 01:10:46'),
(23, 6, 1, 'oglogolo', 1, '2026-04-23 01:11:52'),
(24, 6, 1, '86o99', 1, '2026-04-23 01:11:58'),
(25, 6, 1, '86o99', 1, '2026-04-23 01:12:23'),
(26, 6, 1, 'o96oo', 1, '2026-04-23 01:12:28'),
(27, 6, 1, 'ol9o9o9', 1, '2026-04-23 01:12:52'),
(28, 6, 1, 'ol9o9o9', 1, '2026-04-23 01:15:10'),
(29, 6, 1, '07l7k7lylo', 1, '2026-04-23 01:15:19'),
(30, 6, 1, '7tu7tu', 1, '2026-04-23 01:15:25'),
(31, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:05'),
(32, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:17'),
(33, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:20'),
(34, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:23'),
(35, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:25'),
(36, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:27'),
(37, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:30'),
(38, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:32'),
(39, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:35'),
(40, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:37'),
(41, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:42'),
(42, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:31:44'),
(43, 1, 6, '99IOTG', 0, '2026-04-23 02:31:54'),
(44, 1, 6, '99IOTG', 0, '2026-04-23 02:32:03'),
(45, 1, 6, '99IOTG', 0, '2026-04-23 02:32:06'),
(46, 1, 6, '99IOTG', 0, '2026-04-23 02:32:08'),
(47, 1, 6, '99IOTG', 0, '2026-04-23 02:32:11'),
(48, 1, 6, '99IOTG', 0, '2026-04-23 02:32:13'),
(49, 1, 6, '99IOTG', 0, '2026-04-23 02:32:15'),
(50, 1, 6, '99IOTG', 0, '2026-04-23 02:32:18'),
(51, 1, 6, '99IOTG', 0, '2026-04-23 02:32:20'),
(52, 1, 6, '99IOTG', 0, '2026-04-23 02:32:23'),
(53, 1, 6, '99IOTG', 0, '2026-04-23 02:32:27'),
(54, 1, 6, '99IOTG', 0, '2026-04-23 02:32:29'),
(55, 1, 6, '99IOTG', 0, '2026-04-23 02:32:32'),
(56, 1, 6, '99IOTG', 0, '2026-04-23 02:32:35'),
(57, 1, 6, '99IOTG', 0, '2026-04-23 02:32:38'),
(58, 1, 6, '99IOTG', 0, '2026-04-23 02:32:40'),
(59, 1, 6, '99IOTG', 0, '2026-04-23 02:32:43'),
(60, 1, 6, '99IOTG', 0, '2026-04-23 02:32:46'),
(61, 1, 6, '99IOTG', 0, '2026-04-23 02:32:48'),
(62, 1, 6, '99IOTG', 0, '2026-04-23 02:32:51'),
(63, 1, 6, '99IOTG', 0, '2026-04-23 02:32:54'),
(64, 1, 6, '99IOTG', 0, '2026-04-23 02:32:56'),
(65, 1, 6, '99IOTG', 0, '2026-04-23 02:32:58'),
(66, 1, 6, '99IOTG', 0, '2026-04-23 02:33:08'),
(67, 1, 6, '99IOTG', 0, '2026-04-23 02:33:11'),
(68, 1, 6, '99IOTG', 0, '2026-04-23 02:33:14'),
(69, 1, 6, '99IOTG', 0, '2026-04-23 02:33:16'),
(70, 1, 6, '99IOTG', 0, '2026-04-23 02:33:19'),
(71, 1, 6, '99IOTG', 0, '2026-04-23 02:33:22'),
(72, 1, 6, '99IOTG', 0, '2026-04-23 02:33:24'),
(73, 1, 6, '99IOTG', 0, '2026-04-23 02:33:27'),
(74, 1, 6, '99IOTG', 0, '2026-04-23 02:33:29'),
(75, 1, 6, '99IOTG', 0, '2026-04-23 02:33:32'),
(76, 1, 6, '99IOTG', 0, '2026-04-23 02:33:35'),
(77, 1, 6, '99IOTG', 0, '2026-04-23 02:33:38'),
(78, 1, 6, '99IOTG', 0, '2026-04-23 02:33:41'),
(79, 1, 6, '99IOTG', 0, '2026-04-23 02:33:44'),
(80, 1, 6, '99IOTG', 0, '2026-04-23 02:33:47'),
(81, 1, 6, '99IOTG', 0, '2026-04-23 02:33:49'),
(82, 1, 6, '99IOTG', 0, '2026-04-23 02:33:54'),
(83, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:34:03'),
(84, 1, 6, 'ki7tik7ki', 0, '2026-04-23 02:34:08'),
(85, 1, 6, 'Y88O98O8O', 0, '2026-04-23 02:34:22'),
(86, 1, 6, 'y8o8oy', 0, '2026-04-23 02:36:28'),
(87, 1, 6, 'ki7tik7ki', 0, '2026-04-23 02:36:48'),
(88, 1, NULL, 'jfmfmj', 0, '2026-04-23 03:23:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `repuesto_id` int NOT NULL,
  `tipo` enum('entrada','salida','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL,
  `orden_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `nota` text COLLATE utf8mb4_unicode_ci,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `repuesto_id` (`repuesto_id`),
  KEY `orden_id` (`orden_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `repuesto_id`, `tipo`, `cantidad`, `orden_id`, `usuario_id`, `nota`, `fecha`) VALUES
(1, 3, 'salida', 1, 1, 1, NULL, '2026-04-18 00:15:33'),
(2, 1, 'salida', 1, 9, 1, NULL, '2026-04-18 01:27:09'),
(3, 4, 'salida', 2, 10, 1, NULL, '2026-04-18 01:30:01'),
(4, 2, 'salida', 1, 10, 1, NULL, '2026-04-18 01:31:11'),
(5, 1, 'salida', 1, 8, 1, NULL, '2026-04-18 02:11:57'),
(6, 1, 'salida', 1, 12, 1, NULL, '2026-04-18 11:52:23'),
(7, 4, 'salida', 1, 7, 1, NULL, '2026-04-23 00:00:02'),
(8, 4, 'salida', 1, 5, 1, NULL, '2026-04-23 00:24:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text,
  `tipo` varchar(20) DEFAULT 'info',
  `usuario_id` int DEFAULT NULL,
  `orden_id` int DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `leida` tinyint DEFAULT '0',
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_leida` (`leida`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `titulo`, `mensaje`, `tipo`, `usuario_id`, `orden_id`, `link`, `leida`, `fecha`) VALUES
(1, 'Nuevo mensaje de Administrador', 'Y88O98O8O', 'chat', 6, NULL, '0', 0, '2026-04-22 23:34:03'),
(2, 'Nuevo mensaje de Administrador', 'ki7tik7ki', 'chat', 6, NULL, '0', 0, '2026-04-22 23:34:08'),
(3, 'Nuevo mensaje de Administrador', 'Y88O98O8O', 'chat', 6, NULL, '0', 0, '2026-04-22 23:34:22'),
(4, 'Nuevo mensaje de Administrador', 'y8o8oy', 'chat', 6, NULL, '0', 0, '2026-04-22 23:36:28'),
(5, 'Nuevo mensaje de Administrador', 'ki7tik7ki', 'chat', 6, NULL, '0', 0, '2026-04-22 23:36:48'),
(6, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-008) ahora está: Su equipo necesita repuestos.', 'estado', NULL, 8, 'http://localhost/SistemaSAT/ordenes/ver.php?id=8', 0, '2026-04-22 23:53:17'),
(7, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-008) ahora está: Su equipo necesita repuestos.', 'estado', NULL, 8, 'http://localhost/SistemaSAT/ordenes/ver.php?id=8', 0, '2026-04-22 23:53:28'),
(8, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-008) ahora está: Su equipo necesita repuestos.', 'estado', NULL, 8, 'http://localhost/SistemaSAT/ordenes/ver.php?id=8', 0, '2026-04-22 23:53:45'),
(9, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-008) ahora está: Su equipo necesita repuestos.', 'estado', NULL, 8, 'http://localhost/SistemaSAT/ordenes/ver.php?id=8', 0, '2026-04-22 23:53:52'),
(10, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-008) ahora está: ¡Su equipo ha sido reparado! Puede pasar a retirarlo.', 'estado', NULL, 8, 'http://localhost/SistemaSAT/ordenes/ver.php?id=8', 0, '2026-04-22 23:54:07'),
(11, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-008) ahora está: ¡Su equipo ha sido reparado! Puede pasar a retirarlo.', 'estado', 2, 8, 'http://localhost/SistemaSAT/ordenes/ver.php?id=8', 0, '2026-04-22 23:55:55'),
(12, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-002) ahora está: Su equipo está en proceso de diagnóstico.', 'estado', 2, 2, 'http://localhost/SistemaSAT/ordenes/ver.php?id=2', 0, '2026-04-22 23:56:27'),
(13, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-002) ahora está: Su equipo está en proceso de diagnóstico.', 'estado', 2, 2, 'http://localhost/SistemaSAT/ordenes/ver.php?id=2', 0, '2026-04-22 23:57:14'),
(14, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-002) ahora está: Su equipo está en proceso de diagnóstico.', 'estado', 2, 2, 'http://localhost/SistemaSAT/ordenes/ver.php?id=2', 0, '2026-04-22 23:57:36'),
(15, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-002) ahora está: Su equipo está en proceso de diagnóstico.', 'estado', 2, 2, 'http://localhost/SistemaSAT/ordenes/ver.php?id=2', 0, '2026-04-22 23:57:43'),
(16, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-002) ahora está: La reparación de su equipo está en curso.', 'estado', 2, 2, 'http://localhost/SistemaSAT/ordenes/ver.php?id=2', 0, '2026-04-22 23:58:13'),
(17, 'Nuevo mensaje global de Administrador', 'jfmfmj', 'chat', 2, NULL, '0', 0, '2026-04-23 00:23:28'),
(18, 'Nuevo mensaje global de Administrador', 'jfmfmj', 'chat', 3, NULL, '0', 0, '2026-04-23 00:23:28'),
(19, 'Nuevo mensaje global de Administrador', 'jfmfmj', 'chat', 4, NULL, '0', 0, '2026-04-23 00:23:28'),
(20, 'Nuevo mensaje global de Administrador', 'jfmfmj', 'chat', 5, NULL, '0', 0, '2026-04-23 00:23:28'),
(21, 'Nuevo mensaje global de Administrador', 'jfmfmj', 'chat', 6, NULL, '0', 0, '2026-04-23 00:23:28'),
(22, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-005) ahora está: La reparación de su equipo está en curso.', 'estado', 2, 5, 'http://localhost/SistemaSAT/ordenes/ver.php?id=5', 0, '2026-04-23 00:24:08'),
(23, 'Notificación de Orden', 'Hola Marcelo, le informamos que su equipo (Orden: SAT-20260418-005) ahora está: ¡Su equipo ha sido reparado! Puede pasar a retirarlo.', 'estado', 2, 5, 'http://localhost/SistemaSAT/ordenes/ver.php?id=5', 0, '2026-04-23 00:25:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_servicio`
--

DROP TABLE IF EXISTS `ordenes_servicio`;
CREATE TABLE IF NOT EXISTS `ordenes_servicio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipo_id` int NOT NULL,
  `tecnico_id` int DEFAULT NULL,
  `cliente_id` int NOT NULL,
  `estado` enum('recibido','en_diagnostico','en_reparacion','esperando_repuestos','reparado','entregado','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'recibido',
  `prioridad` enum('baja','normal','alta','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `diagnostico` text COLLATE utf8mb4_unicode_ci,
  `solucion` text COLLATE utf8mb4_unicode_ci,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `fecha_ingreso` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_diagnostico` datetime DEFAULT NULL,
  `fecha_reparacion` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `tiempo_estimado` int DEFAULT NULL,
  `costo_diagnostico` decimal(10,2) DEFAULT '0.00',
  `costo_reparacion` decimal(10,2) DEFAULT '0.00',
  `costo_total` decimal(10,2) DEFAULT '0.00',
  `nota_cliente` text COLLATE utf8mb4_unicode_ci,
  `estado_orden` enum('abierta','cerrada','cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'abierta',
  `sucursal_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `equipo_id` (`equipo_id`),
  KEY `tecnico_id` (`tecnico_id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ordenes_servicio`
--

INSERT INTO `ordenes_servicio` (`id`, `codigo`, `equipo_id`, `tecnico_id`, `cliente_id`, `estado`, `prioridad`, `diagnostico`, `solucion`, `observaciones`, `fecha_ingreso`, `fecha_diagnostico`, `fecha_reparacion`, `fecha_entrega`, `tiempo_estimado`, `costo_diagnostico`, `costo_reparacion`, `costo_total`, `nota_cliente`, `estado_orden`, `sucursal_id`) VALUES
(1, 'SAT-20260418-001', 2, 1, 2, 'entregado', 'normal', 'xgjxgnv', 'nxvcncvnxcvnxc', NULL, '2026-04-17 23:28:39', NULL, '2026-04-18 00:16:11', '2026-04-21 22:49:07', 3, 12345.00, 233.00, 12578.00, 'ZDZCZZ', 'cerrada', NULL),
(2, 'SAT-20260418-002', 2, 1, 2, 'en_reparacion', 'alta', 'zbcvb', 'vcbcvbc', NULL, '2026-04-18 00:11:02', '2026-04-22 23:57:43', '2026-04-21 22:50:44', '2026-04-18 03:01:36', 3, 500.00, 0.00, 500.00, '', 'cerrada', NULL),
(3, 'SAT-20260418-003', 4, 1, 3, 'entregado', 'urgente', NULL, NULL, NULL, '2026-04-18 01:19:21', NULL, NULL, '2026-04-18 03:01:01', 3, 55.00, 0.00, 0.00, 'dfgdfg', 'abierta', NULL),
(4, 'SAT-20260418-004', 2, 1, 2, 'reparado', 'normal', '', '', NULL, '2026-04-18 01:22:56', NULL, '2026-04-22 21:25:14', NULL, 3, 0.00, 7474.00, 7474.00, '', 'cerrada', NULL),
(5, 'SAT-20260418-005', 3, 1, 2, 'reparado', 'normal', '', '', NULL, '2026-04-18 01:23:14', NULL, '2026-04-23 00:25:16', NULL, 3, 0.00, 4141.00, 4141.00, '', 'cerrada', NULL),
(6, 'SAT-20260418-006', 3, 1, 2, 'entregado', 'normal', NULL, NULL, NULL, '2026-04-18 01:24:38', NULL, NULL, '2026-04-18 02:59:43', 3, 0.00, 0.00, 0.00, '', 'abierta', NULL),
(7, 'SAT-20260418-007', 3, 1, 2, 'reparado', 'normal', '', '', NULL, '2026-04-18 01:24:40', NULL, '2026-04-21 22:50:07', '2026-04-18 02:57:56', 3, 0.00, 2222.00, 2222.00, '', 'cerrada', NULL),
(8, 'SAT-20260418-008', 3, 1, 2, 'reparado', 'normal', '', '', NULL, '2026-04-18 01:26:26', NULL, '2026-04-22 23:55:55', NULL, 3, 0.00, 10000.00, 10000.00, '', 'cerrada', NULL),
(9, 'SAT-20260418-009', 1, 1, 3, 'reparado', 'normal', '', '', NULL, '2026-04-18 01:26:58', NULL, '2026-04-18 02:33:42', NULL, 3, 0.00, 400.00, 400.00, '', 'cerrada', NULL),
(10, 'SAT-20260418-010', 5, 1, 4, 'en_reparacion', 'urgente', '', '', NULL, '2026-04-18 01:29:53', NULL, '2026-04-18 01:30:42', NULL, 3, 0.00, 1200.00, 1200.00, '', 'cerrada', NULL),
(12, 'SAT-20260418-011', 5, 1, 4, 'entregado', 'normal', '', '', NULL, '2026-04-18 11:52:14', NULL, '2026-04-18 11:52:42', '2026-04-22 00:52:26', 3, 1222.00, 0.00, 1222.00, '', 'cerrada', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presupuestos`
--

DROP TABLE IF EXISTS `presupuestos`;
CREATE TABLE IF NOT EXISTS `presupuestos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_presupuesto` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden_id` int DEFAULT NULL,
  `cliente_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `igv` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado','convertido') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `validez_dias` int DEFAULT '15',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_presupuesto` (`numero_presupuesto`),
  KEY `orden_id` (`orden_id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `presupuestos`
--

INSERT INTO `presupuestos` (`id`, `numero_presupuesto`, `orden_id`, `cliente_id`, `subtotal`, `igv`, `total`, `estado`, `validez_dias`, `observaciones`, `fecha_creacion`) VALUES
(1, 'P-2026-000001', 10, 4, 500.00, 90.00, 590.00, 'aprobado', 15, '', '2026-04-18 02:28:07'),
(2, 'P-2026-000002', 10, 4, 500.00, 90.00, 590.00, 'pendiente', 15, '', '2026-04-18 02:28:20'),
(3, 'P-2026-000003', 9, 3, 200.00, 36.00, 236.00, 'convertido', 15, '', '2026-04-18 02:35:22'),
(4, 'P-2026-000004', 12, 4, 1222.00, 219.96, 1441.96, 'convertido', 15, '', '2026-04-18 11:53:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuestos`
--

DROP TABLE IF EXISTS `repuestos`;
CREATE TABLE IF NOT EXISTS `repuestos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca_compatible` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo_compatible` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int DEFAULT '0',
  `stock_minimo` int DEFAULT '5',
  `precio_compra` decimal(10,2) DEFAULT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  `ubicacion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo','descontinuado') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `sucursal_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `repuestos`
--

INSERT INTO `repuestos` (`id`, `codigo`, `nombre`, `descripcion`, `categoria`, `marca_compatible`, `modelo_compatible`, `stock`, `stock_minimo`, `precio_compra`, `precio_venta`, `ubicacion`, `estado`, `sucursal_id`) VALUES
(1, 'DIS-001', 'Pantalla LED 15.6', 'Pantalla LED compatible 15.6 pulgadas', 'Pantallas', NULL, NULL, 2, 3, 120.00, 180.00, NULL, 'activo', NULL),
(2, 'TEC-001', 'Teclado Español', 'Teclado para notebook español', 'Teclados', NULL, NULL, 7, 5, 35.00, 55.00, NULL, 'activo', NULL),
(3, 'DIS-002', 'Disco SSD 256GB', 'Disco sólido SSD 256GB SATA', 'Almacenamiento', NULL, NULL, 9, 5, 45.00, 75.00, NULL, 'activo', NULL),
(4, 'BAT-001', 'Batería Original', 'Batería para notebook 6 celdas', 'Baterías', NULL, NULL, 0, 3, 50.00, 85.00, NULL, 'activo', NULL),
(5, 'CAR-001', 'Cargador 19V 3.42A', 'Cargador universal para notebooks', 'Cargadores', NULL, NULL, 12, 5, 25.00, 45.00, NULL, 'activo', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuestos_orden`
--

DROP TABLE IF EXISTS `repuestos_orden`;
CREATE TABLE IF NOT EXISTS `repuestos_orden` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `repuesto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `repuesto_id` (`repuesto_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `repuestos_orden`
--

INSERT INTO `repuestos_orden` (`id`, `orden_id`, `repuesto_id`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 3, 1, 75.00),
(2, 9, 1, 1, 180.00),
(3, 10, 4, 2, 85.00),
(4, 10, 2, 1, 55.00),
(5, 8, 1, 1, 180.00),
(6, 12, 1, 1, 180.00),
(7, 7, 4, 1, 85.00),
(8, 5, 4, 1, 85.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

DROP TABLE IF EXISTS `sucursales`;
CREATE TABLE IF NOT EXISTS `sucursales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `responsable_id` int DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id`, `nombre`, `direccion`, `telefono`, `email`, `responsable_id`, `estado`, `fecha_creacion`) VALUES
(1, 'Matriz', 'Principal', NULL, NULL, NULL, 'activo', '2026-04-22 03:42:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','tecnico','ventas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'tecnico',
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `comision_venta` decimal(5,2) DEFAULT '10.00',
  `comision_presentismo` decimal(5,2) DEFAULT '5.00',
  `comision_especial` decimal(5,2) DEFAULT '15.00',
  `two_factor_secret` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `telefono`, `fecha_creacion`, `estado`, `comision_venta`, `comision_presentismo`, `comision_especial`, `two_factor_secret`, `two_factor_enabled`) VALUES
(1, 'Administrador', 'admin@sat.com', '$2y$12$RZsOpRcoVkxrAHjWBuaVtu/b2EBJyFHjHQCBBi/WGiV8yRH462HQ2', 'admin', '999999999', '2026-04-18 01:38:59', 'activo', 10.00, 5.00, 15.00, NULL, 0),
(2, 'Marcelo', 'marcelo@sat.com', '$2y$12$8QgudTyBTPaxOPuXdWf.z.p0t9ir7bhgtCuYlKk1UPUbR2kixQilG', 'ventas', '99991111', '2026-04-22 21:46:13', 'activo', 10.00, 5.00, 15.00, NULL, 0),
(3, 'fabian', 'fabian@sat.com', '$2y$12$rqb3LeNL/UuU67XGr6ByQO/fRiB7q1fwMhu/umOrt56DunFQ9lGEW', 'tecnico', '33333333', '2026-04-22 22:18:35', 'activo', 10.00, 5.00, 15.00, NULL, 0),
(4, 'Maarcelo', 'maarcelo@sat.com', '$2y$12$1p3LeLc7FpYLBr30MYbDw.vNHj6SUU0EssdkJ1ea6fmC0Eet6NGvS', 'ventas', '937493749', '2026-04-22 22:25:48', 'activo', 10.00, 5.00, 15.00, NULL, 0),
(5, 'a', 'a@sat.com', '$2y$12$M/1QUtSukWT56btVh/tUvuppH1.ynMsLCA3th3MEDNt5mo8lANniC', 'ventas', '23232323', '2026-04-22 22:43:40', 'activo', 10.00, 5.00, 15.00, NULL, 0),
(6, 'benjamin', 'benjamin@sat.com', '$2y$12$w16y9zZ4Tx4pR6FQXjhTCeTPoaYbZKxeOGTfRP3.VuSIMxqBxBMvC', 'ventas', '8347583573', '2026-04-22 22:44:19', 'activo', 10.00, 5.00, 15.00, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_facturas_mes`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_facturas_mes`;
CREATE TABLE IF NOT EXISTS `vista_facturas_mes` (
`mes` int
,`total` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_ordenes_estado`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_ordenes_estado`;
CREATE TABLE IF NOT EXISTS `vista_ordenes_estado` (
`estado` enum('recibido','en_diagnostico','en_reparacion','esperando_repuestos','reparado','entregado','cancelado')
,`total` bigint
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_facturas_mes`
--
DROP TABLE IF EXISTS `vista_facturas_mes`;

DROP VIEW IF EXISTS `vista_facturas_mes`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_facturas_mes`  AS SELECT month(`facturas`.`fecha_emision`) AS `mes`, sum(`facturas`.`total`) AS `total` FROM `facturas` WHERE (year(`facturas`.`fecha_emision`) = year(curdate())) GROUP BY month(`facturas`.`fecha_emision`) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_ordenes_estado`
--
DROP TABLE IF EXISTS `vista_ordenes_estado`;

DROP VIEW IF EXISTS `vista_ordenes_estado`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_ordenes_estado`  AS SELECT `ordenes_servicio`.`estado` AS `estado`, count(0) AS `total` FROM `ordenes_servicio` WHERE (`ordenes_servicio`.`estado_orden` = 'abierta') GROUP BY `ordenes_servicio`.`estado` ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
