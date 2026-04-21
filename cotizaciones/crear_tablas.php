<?php
require_once '../config/database.php';
require_once '../config/config.php';

$conn = getConnection();

$sql = "CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_cotizacion VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    equipo_id INT,
    descripcion TEXT,
    validez_dias INT DEFAULT 7,
    estado ENUM('pendiente', 'aprobado', 'rechazado', 'convertido') DEFAULT 'pendiente',
    observaciones TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE SET NULL
)";

$conn->query($sql);

$sql2 = "CREATE TABLE IF NOT EXISTS detalle_cotizacion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cotizacion_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE
)";

$conn->query($sql2);

echo "Tablas de cotizaciones creadas";