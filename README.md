# Sistema de Gestión de Servicio Técnico (SAT)

Sistema completo para la gestión de reparaciones de equipos de cómputo, desarrollado en PHP puro y MySQL.

## Características

- **Gestión de Órdenes de Servicio (Tickets)**: Creación y seguimiento de órdenes con código único (SAT-YYYYMMDD-XXX)
- **Seguimiento de Estados**: Actualización en tiempo real del estado de los equipos (recibido → en diagnóstico → en reparación → reparado → entregado)
- **Gestión de Inventario**: Control de repuestos con alertas de stock mínimo
- **Facturación y POS**: Generación de facturas con cálculo automático de IGV (18%)
- **Comunicación con Cliente**: Registro automático de notificaciones por cambio de estado

## Requisitos

- PHP 7.4+ con extensión MySQLi
- MySQL 5.7+
- Servidor web (Apache/Nginx)
- Extensiones requeridas: mysqli, json, gd

## Instalación

### 1. Importar Base de Datos

1. Abre phpMyAdmin o tu cliente MySQL favorito
2. Crea una nueva base de datos llamada `sat_db`
3. Importa el archivo `database.sql`

```sql
CREATE DATABASE sat_db;
USE sat_db;
-- Importar database.sql
```

### 2. Configurar Conexión

Edita el archivo `config/database.php` si tu configuración de MySQL es diferente:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Tu contraseña MySQL
define('DB_NAME', 'sat_db');
```

### 3. Configurar Apache/Nginx

Configura tu servidor web para apuntar al directorio del proyecto:

**Apache (httpd-vhosts.conf):**
```apache
<VirtualHost *:80>
    DocumentRoot "C:/wamp64/www/SistemaSAT"
    ServerName sat.local
</VirtualHost>
```

**Nginx (nginx.conf):**
```nginx
server {
    listen 80;
    server_name sat.local;
    root C:/wamp64/www/SistemaSAT;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. Credenciales de Acceso

Usuario por defecto:
- **Email:** admin@sat.com
- **Contraseña:** password

## Estructura del Proyecto

```
/SistemaSAT
├── config/           # Configuración de base de datos
├── include/          # Funciones y componentes reutilizables
├── autenticacion/    # Login, logout, perfil
├── clientes/         # Gestión de clientes
├── equipos/          # Gestión de equipos
├── ordenes/          # Órdenes de servicio (tickets)
├── inventario/      # Control de repuestos
├── facturación/      # Generación de facturas
├── dashboard/        # Estadísticas
├── api/             # Endpoints AJAX
├── css/             # Estilos
├── js/              # Scripts
├── database.sql     # Estructura de base de datos
└── SPEC.md          # Especificación del sistema
```

## Estados de Órden

1. **Recibido** - Equipo recibido del cliente
2. **En Diagnóstico** - Técnico evaluando el problema
3. **En Reparación** - Reparación en proceso
4. **Esperando Repuestos** - Necesita componentes
5. **Reparado** - Reparación completada
6. **Entregado** - Equipo entregado al cliente
7. **Cancelado** - Orden cancelada

## Funcionalidades

### Dashboard
- Estadísticas de órdenes por estado
- Ingresos del mes
- Clientes nuevos
- Alertas de stock bajo
- Órdenes recientes

### Órdenes de Servicio
- Creación automática de código único
- Asignación de técnico
- Registro de diagnóstico y solución
- Uso de repuestos con control de inventario
- Historial de cambios de estado

### Facturación
- Cálculo automático de subtotal, IGV y total
- Detalle de servicios y repuestos
- Métodos de pago: efectivo, tarjeta, transferencia, crédito
- Impresión de facturas

### Notificaciones
- Registro automático de notificaciones al cambiar estado
- Historial de comunicaciones

## Licencia

Desarrollado para uso educativo y comercial.