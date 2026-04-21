<?php
require_once '../config/database.php';
$conn = getConnection();
$conn->query("ALTER TABLE usuarios ADD COLUMN comision_venta DECIMAL(5,2) DEFAULT 10");
$conn->query("ALTER TABLE usuarios ADD COLUMN comision_presentismo DECIMAL(5,2) DEFAULT 5");
$conn->query("ALTER TABLE usuarios ADD COLUMN comision_especial DECIMAL(5,2) DEFAULT 15");
echo "Columnas de comisiones agregadas";