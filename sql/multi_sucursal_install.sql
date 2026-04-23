-- Tabla de Sucursales
CREATE TABLE IF NOT EXISTS sucursales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    email VARCHAR(100),
    responsable_id INT DEFAULT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar sucursal_id a usuarios (si la columna no existe)
-- ALTER TABLE usuarios ADD COLUMN sucursal_id INT DEFAULT NULL;

-- Agregar sucursal_id a ordenes_servicio
ALTER TABLE ordenes_servicio ADD COLUMN sucursal_id INT DEFAULT NULL;

-- Agregar sucursal_id a clientes
ALTER TABLE clientes ADD COLUMN sucursal_id INT DEFAULT NULL;

-- Agregar sucursal_id a inventario
ALTER TABLE repuestos ADD COLUMN sucursal_id INT DEFAULT NULL;

-- Insertar sucursal matriz por defecto
INSERT INTO sucursales (id, nombre, direccion, estado) VALUES (1, 'Matriz', 'Principal', 'activo') ON DUPLICATE KEY UPDATE nombre = 'Matriz';

-- Agregar 2FA a usuarios
ALTER TABLE usuarios ADD COLUMN two_factor_secret VARCHAR(32) DEFAULT NULL;
ALTER TABLE usuarios ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0;

-- Tabla de códigos de verificación (crear solo si usuarios existe)
-- CREATE TABLE IF NOT EXISTS codigos_verificacion (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     usuario_id INT NOT NULL,
--     codigo VARCHAR(10) NOT NULL,
--     tipo VARCHAR(20) NOT NULL,
--     expires_at DATETIME NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
--     INDEX idx_usuario_tipo (usuario_id, tipo),
--     INDEX idx_expires (expires_at)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de configuraciones
CREATE TABLE IF NOT EXISTS configuraciones (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar configuración de backup por defecto
INSERT IGNORE INTO configuraciones (clave, valor) VALUES 
('backup_config', '{"auto_backup":false,"frecuencia":"diario","hora":"02:00","retener":7,"email_notif":false}');