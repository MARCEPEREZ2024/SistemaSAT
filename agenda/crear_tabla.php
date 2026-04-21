<?php
require_once '../config/database.php';
require_once '../config/config.php';

$conn = getConnection();

$conn->query("CREATE TABLE IF NOT EXISTS agenda_entregas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orden_id INT NOT NULL,
    fecha_entrega DATE NOT NULL,
    hora_entrega VARCHAR(10),
    nota TEXT,
    recordatorio_enviado TINYINT DEFAULT 0,
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE
)");

echo "Tabla agenda creada";