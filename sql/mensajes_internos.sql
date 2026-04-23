-- Tabla de Mensajes Internos
CREATE TABLE IF NOT EXISTS mensajes_internos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitente_id INT NOT NULL,
    destinatario_id INT DEFAULT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_remitente (remitente_id),
    INDEX idx_destinatario (destinatario_id),
    INDEX idx_leido (leido),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar FK si la tabla usuarios existe
-- ALTER TABLE mensajes_internos ADD FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE;
-- ALTER TABLE mensajes_internos ADD FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE;