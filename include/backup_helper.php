<?php

function realizarBackup($conn, $outputDir = null) {
    $outputDir = $outputDir ?: dirname(__DIR__) . '/backups';
    
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $fecha = date('Y-m-d_His');
    $filename = "sat_backup_{$fecha}.sql";
    $filepath = $outputDir . '/' . $filename;
    
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $sql = "";
    $sql .= "-- Backup Sistema SAT\n";
    $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM $table");
        $numFields = $result->field_count;
        
        $sql .= "DROP TABLE IF EXISTS $table;\n";
        $createTable = $conn->query("SHOW CREATE TABLE $table");
        $createRow = $createTable->fetch_row();
        $sql .= $createRow[1] . ";\n\n";
        
        while ($row = $result->fetch_row()) {
            $sql .= "INSERT INTO $table VALUES(";
            for ($i = 0; $i < $numFields; $i++) {
                $row[$i] = addslashes($row[$i]);
                $row[$i] = str_replace("\n", "\\n", $row[$i]);
                $sql .= $row[$i] ? "'" . $row[$i] . "'" : "NULL";
                if ($i < $numFields - 1) $sql .= ",";
            }
            $sql .= ");\n";
        }
        $sql .= "\n";
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    if (file_put_contents($filepath, $sql)) {
        compressBackup($filepath);
        logBackup($conn, $filename, filesize($filepath . '.gz'));
        return [
            'success' => true,
            'filename' => $filename . '.gz',
            'size' => filesize($filepath . '.gz')
        ];
    }
    
    return ['success' => false, 'error' => 'No se pudo crear el backup'];
}

function compressBackup($filepath) {
    $gzFile = $filepath . '.gz';
    $source = file_get_contents($filepath);
    $gz = gzencode($source, 9);
    file_put_contents($gzFile, $gz);
    unlink($filepath);
    return $gzFile;
}

function logBackup($conn, $filename, $size) {
    $stmt = $conn->prepare("
        INSERT INTO activity_log (user_id, accion, detalles, ip, fecha)
        VALUES (?, 'backup', ?, ?, NOW())
    ");
    $user_id = $_SESSION['user_id'] ?? 0;
    $detalles = json_encode(['archivo' => $filename, 'size' => $size]);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt->bind_param("iss", $user_id, $detalles, $ip);
    $stmt->execute();
    $stmt->close();
}

function listarBackups($dir = null) {
    $dir = $dir ?: dirname(__DIR__) . '/backups';
    $backups = [];
    
    if (!is_dir($dir)) return $backups;
    
    $files = glob($dir . '/*.sql.gz');
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'modified' => filemtime($file)
        ];
    }
    
    usort($backups, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $backups;
}

function eliminarBackup($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

function restaurarBackup($conn, $filepath) {
    if (!file_exists($filepath)) {
        return ['success' => false, 'error' => 'Archivo no encontrado'];
    }
    
    $sql = gzdecode(file_get_contents($filepath));
    
    if ($sql === false) {
        return ['success' => false, 'error' => 'Error al leer el archivo'];
    }
    
    $queries = explode(';', $sql);
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && strpos($query, '--') !== 0) {
            if (!$conn->query($query)) {
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                return ['success' => false, 'error' => $conn->error];
            }
        }
    }
    
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    logBackup($conn, basename($filepath), 'restore');
    
    return ['success' => true];
}

function getBackupSettings($conn) {
    $stmt = $conn->prepare("SELECT valor FROM configuraciones WHERE clave = 'backup_config'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row ? json_decode($row['valor'], true) : [
        'auto_backup' => false,
        'frecuencia' => 'diario',
        'hora' => '02:00',
        'retener' => 7,
        'email_notif' => false
    ];
}

function saveBackupSettings($conn, $settings) {
    $json = json_encode($settings);
    
    $stmt = $conn->prepare("
        INSERT INTO configuraciones (clave, valor) VALUES ('backup_config', ?)
        ON DUPLICATE KEY UPDATE valor = ?
    ");
    $stmt->bind_param("ss", $json, $json);
    $stmt->execute();
    $stmt->close();
    
    return true;
}