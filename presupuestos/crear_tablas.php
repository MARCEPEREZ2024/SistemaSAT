<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

$conn = getConnection();

$sql1 = "CREATE TABLE IF NOT EXISTS presupuestos (
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
)";

$sql2 = "CREATE TABLE IF NOT EXISTS detalle_presupuesto (
    id INT PRIMARY KEY AUTO_INCREMENT,
    presupuesto_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (presupuesto_id) REFERENCES presupuestos(id) ON DELETE CASCADE
)";

$sql3 = "CREATE TABLE IF NOT EXISTS garantias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orden_id INT NOT NULL,
    meses INT NOT NULL DEFAULT 3,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    descripcion TEXT,
    estado ENUM('activa', 'vencida', 'usada') DEFAULT 'activa',
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE
)";

$conn->query($sql1);
$conn->query($sql2);
$conn->query($sql3);

echo "Tablas creadas correctamente";