-- Tabla de Audit Trail
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    usuario_nombre VARCHAR(100),
    accion VARCHAR(50) NOT NULL,
    tabla VARCHAR(50),
    registro_id INT,
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    ip VARCHAR(45),
    user_agent VARCHAR(255),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_tabla (tabla),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;