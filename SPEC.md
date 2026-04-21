# Sistema de Gestión de Servicio Técnico (SAT) - Especificación

## 1. Project Overview

**Nombre del Proyecto:** Sistema de Gestión de Servicio Técnico (SAT)
**Tipo:** Aplicación Web PHP con MySQL
**Descripción:** Sistema integral para la gestión de reparaciones de equipos de cómputo (principalmente notebooks), con seguimiento de estados, inventario de repuestos, facturación y notificaciones automáticas a clientes.
**Usuarios Objetivo:** Técnicos de servicio, administradores del taller, clientes

---

## 2. Estructura de Base de Datos

### Tablas Principales

#### `usuarios`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `nombre` VARCHAR(100)
- `email` VARCHAR(100) UNIQUE
- `password` VARCHAR(255)
- `rol` ENUM('admin', 'tecnico', 'atencion') DEFAULT 'tecnico'
- `telefono` VARCHAR(20)
- `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- `estado` ENUM('activo', 'inactivo') DEFAULT 'activo'

#### `clientes`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `nombre` VARCHAR(100)
- `email` VARCHAR(100)
- `telefono` VARCHAR(20)
- `direccion` TEXT
- `dni` VARCHAR(20)
- `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- `estado` ENUM('activo', 'inactivo') DEFAULT 'activo'

#### `equipos`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `cliente_id` INT (FK clientes)
- `marca` VARCHAR(50)
- `modelo` VARCHAR(100)
- `serie` VARCHAR(100)
- `tipo_equipo` ENUM('notebook', 'desktop', 'all-in-one', 'monitor', 'otro')
- `diagnostico_inicial` TEXT
- `passwordBIOS` VARCHAR(50)
- `passwordSO` VARCHAR(50)
- `accesorios` TEXT
- `estado_equipo` ENUM('bueno', 'regular', 'malo')
- `fecha_ingreso` DATE
- `foto_equipo` VARCHAR(255)
- `estado` ENUM('activo', 'retirado', 'dado_de_baja') DEFAULT 'activo'

#### `ordenes_servicio`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `codigo` VARCHAR(20) UNIQUE (Formato: SAT-YYYYMMDD-XXX)
- `equipo_id` INT (FK equipos)
- `tecnico_id` INT (FK usuarios)
- `cliente_id` INT (FK clientes)
- `estado` ENUM('recibido', 'en_diagnostico', 'en_reparacion', 'esperando_repuestos', 'reparado', 'entregado', 'cancelado') DEFAULT 'recibido'
- `prioridad` ENUM('baja', 'normal', 'alta', 'urgente') DEFAULT 'normal'
- `diagnostico` TEXT
- `solucion` TEXT
- `observaciones` TEXT
- `fecha_ingreso` DATETIME DEFAULT CURRENT_TIMESTAMP
- `fecha_diagnostico` DATETIME NULL
- `fecha_reparacion` DATETIME NULL
- `fecha_entrega` DATETIME NULL
- `tiempo_estimado` INT (días)
- `costo_diagnostico` DECIMAL(10,2)
- `costo_reparacion` DECIMAL(10,2)
- `costo_total` DECIMAL(10,2)
- `nota_cliente` TEXT
- `estado_orden` ENUM('abierta', 'cerrada', 'cancelada') DEFAULT 'abierta'

#### `estados_seguimiento`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `orden_id` INT (FK ordenes_servicio)
- `estado` VARCHAR(50)
- `descripcion` TEXT
- `tecnico_id` INT (FK usuarios)
- `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP

#### `repuestos`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `codigo` VARCHAR(50) UNIQUE
- `nombre` VARCHAR(100)
- `descripcion` TEXT
- `categoria` VARCHAR(50)
- `marca_compatible` VARCHAR(100)
- `modelo_compatible` VARCHAR(100)
- `stock` INT DEFAULT 0
- `stock_minimo` INT DEFAULT 5
- `precio_compra` DECIMAL(10,2)
- `precio_venta` DECIMAL(10,2)
- `ubicacion` VARCHAR(50)
- `estado` ENUM('activo', 'inactivo', 'descontinuado') DEFAULT 'activo'

#### `movimientos_inventario`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `repuesto_id` INT (FK repuestos)
- `tipo` ENUM('entrada', 'salida', 'ajuste')
- `cantidad` INT
- `orden_id` INT NULL (FK ordenes_servicio)
- `usuario_id` INT (FK usuarios)
- `nota` TEXT
- `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP

#### `facturas`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `numero_factura` VARCHAR(20) UNIQUE
- `orden_id` INT (FK ordenes_servicio)
- `cliente_id` INT (FK clientes)
- `subtotal` DECIMAL(10,2)
- `igv` DECIMAL(10,2)
- `total` DECIMAL(10,2)
- `tipo_pago` ENUM('efectivo', 'tarjeta', 'transferencia', 'credito')
- `estado_pago` ENUM('pendiente', 'pagado', 'cancelado') DEFAULT 'pendiente'
- `fecha_emision` DATETIME DEFAULT CURRENT_TIMESTAMP
- `fecha_pago` DATETIME NULL
- `observaciones` TEXT

#### `detalle_factura`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `factura_id` INT (FK facturas)
- `descripcion` TEXT
- `cantidad` INT
- `precio_unitario` DECIMAL(10,2)
- `importe` DECIMAL(10,2)

#### `notificaciones`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `cliente_id` INT (FK clientes)
- `orden_id` INT (FK ordenes_servicio)
- `tipo` ENUM('estado', 'recordatorio', 'promocion', 'factura')
- `canal` ENUM('email', 'sms', 'whatsapp')
- `mensaje` TEXT
- `estado` ENUM('pendiente', 'enviado', 'fallido') DEFAULT 'pendiente'
- `fecha_envio` DATETIME NULL
- `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP

#### `configuraciones`
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `clave` VARCHAR(50) UNIQUE
- `valor` TEXT

---

## 3. Estructura de Archivos

```
/SAT
├── config/
│   ├── database.php
│   └── config.php
├── include/
│   ├── funciones.php
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── clases/
│   ├── Usuario.php
│   ├── Cliente.php
│   ├── Equipo.php
│   ├── Orden.php
│   ├── Repuesto.php
│   ├── Factura.php
│   └── Notificacion.php
├── css/
│   └── styles.css
├── js/
│   └── scripts.js
├── assets/
│   └── uploads/
├── autenticacion/
│   ├── login.php
│   ├── logout.php
│   └── perfil.php
├── clientes/
│   ├── listar.php
│   ├── agregar.php
│   ├── editar.php
│   └── ver.php
├── equipos/
│   ├── listar.php
│   ├── agregar.php
│   ├── editar.php
│   └── ver.php
├── ordenes/
│   ├── listar.php
│   ├── agregar.php
│   ├── editar.php
│   ├── ver.php
│   └── cambiar_estado.php
├── inventario/
│   ├── listar.php
│   ├── agregar.php
│   ├── agregar_stock.php
│   └── historial.php
├── facturacion/
│   ├── listar.php
│   ├── crear.php
│   ├── ver.php
│   └── imprimir.php
├── dashboard/
│   └── index.php
├── api/
│   ├── estados.php
│   ├── inventario.php
│   └── notificaciones.php
├── procedimientos/
│   └── notificaciones_auto.php
└── index.php
```

---

## 4. Funcionalidades por Módulo

### 4.1 Autenticación
- Login con email y contraseña
- Control de sesiones seguras
- Roles: admin, técnico, atención
- Perfil de usuario con edición

### 4.2 Gestión de Clientes
- CRUD de clientes
- Búsqueda por nombre, email, teléfono, DNI
- Historial de equipos y órdenes por cliente

### 4.3 Gestión de Equipos
- Registro de equipo con datos completos
- Vinculación con cliente
- Historial de reparaciones por equipo
- Estado físico del equipo

### 4.4 Órdenes de Servicio (Tickets)
- Creación automática de código único SAT-YYYYMMDD-XXX
- Flujo de estados: recibido → en_diagnostico → en_reparacion → esperando_repuestos → reparado → entregado
- Asignación de técnico responsable
- Registro de diagnóstico y solución
- Seguimiento con historial de cambios de estado
- Notas y observaciones

### 4.5 Inventario y Repuestos
- Catálogo de repuestos
- Control de stock con alerta de stock mínimo
- Movimientos de inventario (entrada, salida, ajuste)
- Uso de repuestos en órdenes de servicio
- Historial de movimientos

### 4.6 Facturación y POS
- Generación de factura desde orden de servicio
- Detalle de servicios y repuestos utilizados
- Cálculo automático de IGV (18%)
- Métodos de pago: efectivo, tarjeta, transferencia, crédito
- Estado de pago: pendiente, pagado, cancelado
- Impresión de factura

### 4.7 Notificaciones
- Notificaciones por cambio de estado
- Templates de mensajes predefinidos
- Registro de notificaciones enviadas
- Simulación de envío (logs)

### 4.8 Dashboard
- Estadísticas: órdenes por estado, clientes nuevos, ingresos
- Gráficos visuales
- Órdenes recientes
- Alertas de stock bajo

---

## 5. Interfaz de Usuario

### Diseño General
- Template responsive con Bootstrap 5
- Navegación lateral (sidebar)
- Header con info de usuario
- Colores: azul profesional (#0d6efd), grises claros
- Iconos Bootstrap Icons

### Estados Visuales
- **Recibido:** Gris
- **En Diagnóstico:** Amarillo
- **En Reparación:** Naranja
- **Esperando Repuestos:** Azul claro
- **Reparado:** Verde
- **Entregado:** Verde oscuro
- **Cancelado:** Rojo

---

## 6. Requisitos Técnicos

- PHP 7.4+ con MySQLi
- MySQL 5.7+
- Servidor web (Apache/Nginx)
- Extensiones: mysqli, json, gd, curl
- Sesiones PHP
- AJAX para actualizaciones en tiempo real