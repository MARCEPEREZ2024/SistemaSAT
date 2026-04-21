-- Sistema de Gestión de Servicio Técnico (SAT)
-- Base de Datos MySQL

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sat_db;
USE sat_db;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'tecnico', 'atencion') DEFAULT 'tecnico',
    telefono VARCHAR(20),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20) NOT NULL,
    direccion TEXT,
    dni VARCHAR(20),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Tabla de equipos
CREATE TABLE IF NOT EXISTS equipos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(100),
    serie VARCHAR(100),
    tipo_equipo ENUM('notebook', 'desktop', 'all-in-one', 'monitor', 'otro') DEFAULT 'notebook',
    diagnostico_inicial TEXT,
    passwordBIOS VARCHAR(50),
    passwordSO VARCHAR(50),
    accesorios TEXT,
    estado_equipo ENUM('bueno', 'regular', 'malo') DEFAULT 'regular',
    fecha_ingreso DATE,
    foto_equipo VARCHAR(255),
    estado ENUM('activo', 'retirado', 'dado_de_baja') DEFAULT 'activo',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- Tabla de órdenes de servicio
CREATE TABLE IF NOT EXISTS ordenes_servicio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    equipo_id INT NOT NULL,
    tecnico_id INT,
    cliente_id INT NOT NULL,
    estado ENUM('recibido', 'en_diagnostico', 'en_reparacion', 'esperando_repuestos', 'reparado', 'entregado', 'cancelado') DEFAULT 'recibido',
    prioridad ENUM('baja', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    diagnostico TEXT,
    solucion TEXT,
    observaciones TEXT,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_diagnostico DATETIME NULL,
    fecha_reparacion DATETIME NULL,
    fecha_entrega DATETIME NULL,
    tiempo_estimado INT,
    costo_diagnostico DECIMAL(10,2) DEFAULT 0,
    costo_reparacion DECIMAL(10,2) DEFAULT 0,
    costo_total DECIMAL(10,2) DEFAULT 0,
    nota_cliente TEXT,
    estado_orden ENUM('abierta', 'cerrada', 'cancelada') DEFAULT 'abierta',
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- Tabla de seguimiento de estados
CREATE TABLE IF NOT EXISTS estados_seguimiento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orden_id INT NOT NULL,
    estado VARCHAR(50) NOT NULL,
    descripcion TEXT,
    tecnico_id INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de repuestos
CREATE TABLE IF NOT EXISTS repuestos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50),
    marca_compatible VARCHAR(100),
    modelo_compatible VARCHAR(100),
    stock INT DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    precio_compra DECIMAL(10,2),
    precio_venta DECIMAL(10,2),
    ubicacion VARCHAR(50),
    estado ENUM('activo', 'inactivo', 'descontinuado') DEFAULT 'activo'
);

-- Tabla de movimientos de inventario
CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    repuesto_id INT NOT NULL,
    tipo ENUM('entrada', 'salida', 'ajuste') NOT NULL,
    cantidad INT NOT NULL,
    orden_id INT,
    usuario_id INT,
    nota TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id) ON DELETE CASCADE,
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de facturas
CREATE TABLE IF NOT EXISTS facturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_factura VARCHAR(20) UNIQUE NOT NULL,
    orden_id INT NOT NULL,
    cliente_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    igv DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    tipo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'credito') DEFAULT 'efectivo',
    estado_pago ENUM('pendiente', 'pagado', 'cancelado') DEFAULT 'pendiente',
    fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_pago DATETIME NULL,
    observaciones TEXT,
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- Tabla de detalle de factura
CREATE TABLE IF NOT EXISTS detalle_factura (
    id INT PRIMARY KEY AUTO_INCREMENT,
    factura_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE
);

-- Tabla de notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    orden_id INT NOT NULL,
    tipo ENUM('estado', 'recordatorio', 'promocion', 'factura') NOT NULL,
    canal ENUM('email', 'sms', 'whatsapp') NOT NULL,
    mensaje TEXT NOT NULL,
    estado ENUM('pendiente', 'enviado', 'fallido') DEFAULT 'pendiente',
    fecha_envio DATETIME NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE
);

-- Tabla de configuraciones
CREATE TABLE IF NOT EXISTS configuraciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT
);

-- Tabla de repuestos utilizados en órdenes
CREATE TABLE IF NOT EXISTS repuestos_orden (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orden_id INT NOT NULL,
    repuesto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id) ON DELETE CASCADE
);

-- Tabla de presupuestos
CREATE TABLE IF NOT EXISTS presupuestos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_presupuesto VARCHAR(20) UNIQUE NOT NULL,
    orden_id INT,
    cliente_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    igv DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado', 'convertido') DEFAULT 'pendiente',
    validez_dias INT DEFAULT 15,
    observaciones TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- Tabla de detalle de presupuesto
CREATE TABLE IF NOT EXISTS detalle_presupuesto (
    id INT PRIMARY KEY AUTO_INCREMENT,
    presupuesto_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (presupuesto_id) REFERENCES presupuestos(id) ON DELETE CASCADE
);

-- Tabla de garantías
CREATE TABLE IF NOT EXISTS garantias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orden_id INT NOT NULL,
    meses INT NOT NULL DEFAULT 3,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    descripcion TEXT,
    estado ENUM('activa', 'vencida', 'usada') DEFAULT 'activa',
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE
);

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, email, password, rol, telefono) VALUES
('Administrador', 'admin@sat.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '999999999');

-- Insertar configuraciones por defecto
INSERT INTO configuraciones (clave, valor) VALUES
('empresa_nombre', 'Servicio Técnico SAT'),
('empresa_direccion', 'Av. Principal 123'),
('empresa_telefono', '999-999-999'),
('empresa_ruc', '20123456789'),
('igv_porcentaje', '18'),
('moneda', 'S/');

-- Insertar algunos repuestos de ejemplo
INSERT INTO repuestos (codigo, nombre, descripcion, categoria, precio_compra, precio_venta, stock, stock_minimo) VALUES
('DIS-001', 'Pantalla LED 15.6', 'Pantalla LED compatible 15.6 pulgadas', 'Pantallas', 120.00, 180.00, 5, 3),
('TEC-001', 'Teclado Español', 'Teclado para notebook español', 'Teclados', 35.00, 55.00, 8, 5),
('DIS-002', 'Disco SSD 256GB', 'Disco sólido SSD 256GB SATA', 'Almacenamiento', 45.00, 75.00, 10, 5),
('BAT-001', 'Batería Original', 'Batería para notebook 6 celdas', 'Baterías', 50.00, 85.00, 4, 3),
('CAR-001', 'Cargador 19V 3.42A', 'Cargador universal para notebooks', 'Cargadores', 25.00, 45.00, 12, 5);

-- Vistas para estadísticas
DROP VIEW IF EXISTS vista_ordenes_estado;
CREATE VIEW vista_ordenes_estado AS
SELECT estado, COUNT(*) as total FROM ordenes_servicio WHERE estado_orden = 'abierta' GROUP BY estado;

DROP VIEW IF EXISTS vista_facturas_mes;
CREATE VIEW vista_facturas_mes AS
SELECT MONTH(fecha_emision) as mes, SUM(total) as total FROM facturas WHERE YEAR(fecha_emision) = YEAR(CURRENT_DATE) GROUP BY MONTH(fecha_emision);