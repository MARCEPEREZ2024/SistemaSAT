# Sistema SAT - Sistema de Gestión de Servicio Técnico

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Requisitos del Sistema](#requisitos-del-sistema)
3. [Instalación](#instalación)
4. [Configuración Inicial](#configuración-inicial)
5. [Estructura del Proyecto](#estructura-del-proyecto)
6. [Guía de Módulos](#guía-de-módulos)
7. [Uso del Sistema](#uso-del-sistema)
8. [API y Desarrolladores](#api-y-desarrolladores)
9. [Seguridad](#seguridad)
10. [Mantenimiento](#mantenimiento)
11. [Solución de Problemas](#solución-de-problemas)

---

## Introducción

Sistema SAT es una aplicación web completa para la gestión de talleres de servicio técnico de computadoras y dispositivos electrónicos. Permite administrar órdenes de servicio, inventario, facturación, clientes y más.

### Propósito del Sistema

El sistema está diseñado para:

- **Gestión integral**: Control total de las operaciones del taller
- **Trazabilidad**: Historial completo de cada orden de servicio
- **Productividad**: Automatización de procesos repetitivos
- **Comunicación**: Notificaciones internas y externas
- **Reportes**: Análisis de datos para toma de decisiones

### Funcionalidades Principales

| Módulo | Descripción |
|--------|-------------|
| Órdenes de Servicio | Creación, seguimiento y cierre de órdenes |
| Inventario | Control de repuestos y stock |
| Facturación | Generación de facturas y presupuestos |
| Clientes | Base de datos de clientes |
| Chat Interno | Comunicación entre usuarios |
| Notificaciones | Alertas en tiempo real |
| Reportes | Estadísticas y exportación PDF |
| Usuarios | Gestión de empleados y permisos |

---

## Requisitos del Sistema

### Requisitos del Servidor

| Componente | Versión Mínima | Recomendada |
|------------|---------------|-------------|
| PHP | 8.0 | 8.2+ |
| MySQL | 8.0 | 8.0+ |
| Apache/Nginx | 2.4 | 2.4+ |

### Extensiones PHP Requeridas

```bash
# Verificar extensiones instaladas
php -m

# Extensiones necesarias:
- mysqli
- pdo_mysql
- mbstring
- xml
- zip
- curl
- openssl
- json
- session
```

### Requisitos del Cliente

| Navegador | Versión Mínima |
|-----------|---------------|
| Chrome | 80+ |
| Firefox | 75+ |
| Edge | 80+ |
| Safari | 13+ |

### Herramientas Recomendadas

- WAMP Server (Windows)
- XAMPP (Multiplataforma)
- MAMP (macOS)
- Laragon (Windows)

---

## Instalación

### Paso 1: Preparar el Entorno

#### En Windows con WAMP:

1. Descargar WAMP de https://www.wampserver.com/
2. Instalar WAMP
3. Iniciar WAMP Server
4. Verificar que el icono esté en verde

#### Verificar PHP:

```bash
# En terminal
php -v
# Output esperado: PHP 8.x.x
```

#### Verificar MySQL:

```bash
# En phpMyAdmin o terminal
mysql -u root -p
```

### Paso 2: Descargar el Proyecto

```bash
# Opción 1: Clonar repositorio
cd C:\wamp64\www
git clone https://github.com/tu-usuario/SistemaSAT.git

# Opción 2: Descargar ZIP
# Descargar desde GitHub y extraer en C:\wamp64\www\SistemaSAT
```

### Paso 3: Instalar Dependencias

```bash
# Navegar al directorio del proyecto
cd C:\wamp64\www\SistemaSAT

# Instalar dependencias Composer
composer install

# Verificar instalación
ls vendor/
```

### Paso 4: Configurar Base de Datos

#### Crear Base de Datos:

1. Abrir phpMyAdmin: http://localhost/phpmyadmin
2. Click en "Nueva base de datos"
3. Nombre: `sat_db`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Click en "Crear"

#### Importar Estructura:

1. Seleccionar base de datos `sat_db`
2. Click en "Importar"
3. Seleccionar archivo: `sql/rearme_completo.sql`
4. Click en "Continuar"

### Paso 5: Configurar Conexión

Editar `config/database.php`:

```php
<?php
// Configuración de base de datos

define('DB_HOST', 'localhost');        // Host de MySQL
define('DB_USER', 'root');             // Usuario MySQL
define('DB_PASS', '');                 // Contraseña MySQL
define('DB_NAME', 'sat_db');           // Nombre de la base de datos

// Conexión global
$conn = null;

function getConnection() {
    global $conn;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}
?>
```

### Paso 6: Verificar Instalación

1. Abrir navegador: http://localhost/SistemaSAT/
2. Verificar que cargue la página de login
3. Credenciales por defecto:
   - **Usuario**: admin@sat.com
   - **Contraseña**: admin123

---

## Configuración Inicial

### 1. Configurar Empresa

Acceder a: `http://localhost/SistemaSAT/configuracion/`

Completar datos:
- Nombre de la empresa
- RUC
- Dirección
- Teléfono

### 2. Configurar Email SMTP

Acceder a: `http://localhost/SistemaSAT/configuracion/email.php`

```php
// Configuración ejemplo para Gmail
SMTP Host: smtp.gmail.com
SMTP Port: 587
SMTP User: tuemail@gmail.com
SMTP Pass: xxxx xxxx xxxx xxxx  (App Password)
SMTP Secure: tls
```

#### Cómo obtener App Password de Gmail:

1. Ir a https://myaccount.google.com/
2. Seguridad > Verificación en 2 pasos (activar)
3. Seguridad > Contraseñas de aplicaciones
4. Generar nueva contraseña de 16 caracteres

### 3. Configurar WhatsApp (Opcional)

Acceder a: `http://localhost/SistemaSAT/configuracion/whatsapp.php`

Requiere servidor WPPConnect corriendo.

### 4. Crear Usuarios

1. Ir a: `http://localhost/SistemaSAT/usuarios/listar.php`
2. Click en "Nuevo Usuario"
3. Completar datos:
   - Nombre completo
   - Email (único)
   - Teléfono
   - Rol: admin / técnico / ventas
   - Contraseña

---

## Estructura del Proyecto

```
SistemaSAT/
│
├── api/                          # Endpoints de la API
│   ├── chat_messages.php        # API del chat interno
│   ├── notifications.php        # API de notificaciones
│   ├── mobile.php              # API para móviles
│   └── sse_notificaciones.php # Server-Sent Events
│
├── autenticacion/               # Sistema de autenticación
│   ├── login.php               # Página de login
│   ├── logout.php             # Cerrar sesión
│   └── perfil.php             # Perfil de usuario
│
├── chat/                        # Módulo de chat interno
│   ├── index.php              # Interfaz del chat
│   └── (funciones del chat)
│
├── clientes/                    # Gestión de clientes
│   ├── agregar.php             # Crear cliente
│   ├── editar.php             # Editar cliente
│   ├── listar.php             # Listar clientes
│   └── ver.php                # Ver cliente
│
├── configuracion/               # Configuraciones del sistema
│   ├── email.php              # Configuración SMTP
│   ├── whatsapp.php           # Configuración WhatsApp
│   └── general.php            # Configuración general
│
├── cotizaciones/                # Módulo de cotizaciones
│   ├── agregar.php
│   ├── listar.php
│   └── ver.php
│
├── dashboard/                   # Panel principal
│   ├── index.php              # Dashboard
│   └── notificaciones.php      # Enviar notificaciones
│
├── equipos/                    # Gestión de equipos
│   ├── agregar.php
│   ├── editar.php
│   └── listar.php
│
├── facturacion/                # Facturación
│   ├── crear.php              # Nueva factura
│   ├── listar.php            # Lista de facturas
│   └── ver.php               # Ver factura
│
├── garantias/                  # Sistema de garantías
│   ├── agregar.php
│   └── listar.php
│
├── include/                    # Archivos include
│   ├── header.php             # Encabezado (navbar, sidebar)
│   ├── footer.php             # Pie de página
│   ├── funciones.php          # Funciones globales
│   ├── chat_helper.php        # Funciones del chat
│   ├── email_helper.php       # Funciones de email
│   ├── reporte_pdf_helper.php # Generación PDFs
│   ├── notifications_helper.php
│   ├── csrf_helper.php        # Protección CSRF
│   ├── pagination_helper.php   # Paginación
│   ├── filters_helper.php     # Filtros
│   └── export_helper.php      # Exportación
│
├── inventario/                 # Control de inventario
│   ├── agregar.php
│   ├── agregar_stock.php     # Entrada de stock
│   ├── listar.php
│   └── movimientos.php       # Historial de movimientos
│
├── ordenes/                    # Órdenes de servicio
│   ├── agregar.php            # Nueva orden
│   ├── editar.php            # Editar orden
│   ├── listar.php           # Listar órdenes
│   ├── ver.php              # Ver orden completa
│   └── cambiar_estado.php    # Cambiar estado
│
├── presupuestos/               # Presupuestos
│   ├── agregar.php
│   ├── listar.php
│   └── ver.php
│
├── reportes/                   # Reportes
│   └── index.php
│
├── sql/                        # Scripts SQL
│   ├── database.sql           # Estructura completa
│   ├── rearme_completo.sql   # Rebuild completo
│   └── mensajes_internos.sql  # Chat
│
├── usuarios/                    # Gestión de usuarios
│   ├── agregar.php
│   ├── editar.php
│   ├── listar.php
│   └── perfil.php
│
├── config/                     # Configuración
│   ├── config.php            # Constantes y funciones
│   └── database.php          # Conexión BD
│
├── vendor/                     # Dependencias Composer
│   ├── tecnickcom/tcpdf     # Generación PDF
│   ├── phpmailer/phpmailer # Envío de emails
│   └── cboden/ratchet       # WebSocket
│
├── websocket/                  # Servidor WebSocket
│   ├── server.php            # Iniciar servidor
│   └── NotificacionesSocket.php
│
├── js/                         # JavaScript
│   ├── ajax_utils.js         # Funciones AJAX
│   └── scripts.js            # Scripts generales
│
├── css/                        # Estilos
│   └── styles.css
│
└── index.php                   # Punto de entrada (redirige a login)
```

---

## Guía de Módulos

### 1. Módulo de Órdenes de Servicio

#### Flujo de una Orden

```
Recibido → En Diagnóstico → En Reparación → Esperando Repuestos → Reparado → Entregado
```

#### Estados Posibles

| Estado | Descripción | Color |
|--------|-------------|-------|
| recibido | Orden recibida | Gris |
| en_diagnostico | En análisis | Amarillo |
| en_reparacion | En reparación | Naranja |
| esperando_repuestos | Pendiente de repuestos | Cyan |
| reparado | Equipo reparado | Verde |
| entregado | Entregado al cliente | Verde |
| cancelado | Cancelada | Rojo |

#### Crear Nueva Orden

1. Ir a: `ordenes/agregar.php`
2. Seleccionar cliente
3. Seleccionar equipo (o crear nuevo)
4. Asignar técnico (opcional)
5. Definir prioridad
6. Agregar nota del cliente
7. Click en "Crear Orden"

#### Seguimiento de Estado

1. Ir a: `ordenes/listar.php`
2. Buscar orden
3. Click en el icono de ojo o doble clic
4. En "Cambiar Estado", seleccionar nuevo estado
5. Agregar descripción
6. Click en "Actualizar"

### 2. Módulo de Inventario

#### Agregar Repuesto

1. Ir a: `inventario/agregar.php`
2. Completar:
   - Código (único)
   - Nombre
   - Descripción
   - Categoría
   - Marca/Modelo compatible
   - Stock inicial
   - Stock mínimo (para alertas)
   - Precio de compra
   - Precio de venta

#### Movimientos de Stock

**Entrada:**
- Compra de repuestos
- Devoluciones

**Salida:**
- Uso en reparaciones
- Ventas

**Ajuste:**
- Corrección de inventario

### 3. Módulo de Facturación

#### Crear Factura desde Orden

1. Ir a: `facturacion/crear.php`
2. Seleccionar "Factura desde Orden"
3. Elegir orden (solo showing: reparado + cerrada)
4. Ver detalle automático
5. Click en "Generar Factura"

#### Crear Factura Directa

1. Ir a: `facturacion/crear.php`
2. Seleccionar "Factura Directa"
3. Seleccionar cliente
4. Agregar items manualmente
5. Definir precios
6. Click en "Generar Factura"

### 4. Módulo de Clientes

Cada cliente puede tener múltiples equipos registrados.

#### Datos del Cliente

- Nombre
- Email
- Teléfono
- Dirección
- DNI

#### Equipos del Cliente

Por cada equipo se registra:
- Marca
- Modelo
- Número de serie
- Tipo (notebook/desktop/monitor/otro)
- Contraseñas (BIOS, SO)
- Accesorios

### 5. Chat Interno

#### Características

- Mensajes globales (todos pueden ver)
- Mensajes privados (entre dos usuarios)
- Notificaciones en tiempo real
- Badge con mensajes no leídos

#### Usar el Chat

1. Click en "Chat Interno" del menú
2. Pestaña "Mensajes Globales" para chat público
3. Pestaña "Conversaciones" para mensajes privados

### 6. Notificaciones

#### Tipos de Notificaciones

- **Internas**: Chat, alertas del sistema
- **Externas**: Email, WhatsApp

#### Configurar Notificaciones

1. Ir a: `dashboard/notificaciones.php`
2. Seleccionar orden
3. Escribir mensaje
4. Elegir canal (email/WhatsApp)
5. Click en "Enviar"

---

## Uso del Sistema

### Flujo de Trabajo Típico

#### Recepción de Equipo

1. Registrar cliente (si no existe)
2. Registrar equipo del cliente
3. Crear orden de servicio
4. Asignar técnico

#### Proceso de Reparación

1. Técnico recibe orden
2. Realiza diagnóstico
3. Registra diagnóstico en orden
4. Solicita repuestos (si necesita)
5. Realiza reparación
6. Registra solución
7. Cambia estado a "Reparado"

#### Entrega

1. Cliente confirma reparación
2. Crear factura desde orden
3. Cobrar servicio
4. Cambiar estado a "Entregado"
5. Entregar equipo

### Búsquedas y Filtros

#### Filtrar Órdenes

En `ordenes/listar.php`:
- Por estado
- Por prioridad
- Por técnico
- Por fecha (desde/hasta)
- Por código/cliente/equipo

#### Búsqueda Global

En `buscar/`:
- Buscar por código de orden
- Por nombre de cliente
- Por número de equipo

### Reportes

#### Generar Reporte

1. Ir a: `reportes/index.php`
2. Seleccionar año
3. Seleccionar mes (opcional)
4. Click en "Generar PDF"

#### Contenido del Reporte

- Total de órdenes
- Órdenes por estado
- Órdenes por mes
- Técnicos con más trabajo
- Ingresos

---

## API y Desarrolladores

### API Móvil

Base URL: `http://localhost/SistemaSAT/api/mobile.php`

#### Autenticación

```http
POST /api/mobile.php?action=login
Content-Type: application/json

{
    "email": "admin@sat.com",
    "password": "admin123"
}
```

**Respuesta:**
```json
{
    "success": true,
    "user": {
        "id": 1,
        "nombre": "Administrador",
        "email": "admin@sat.com",
        "rol": "admin"
    },
    "token": "abc123..."
}
```

#### Listar Órdenes

```http
GET /api/mobile.php?action=ordenes&estado=reparado&limit=20
```

#### Detalle de Orden

```http
GET /api/mobile.php?action=orden_detalle&id=1
```

#### Cambiar Estado

```http
POST /api/mobile.php?action=cambiar_estado
Content-Type: application/json

{
    "id": 1,
    "estado": "entregado",
    "nota": "Equipo entregado"
}
```

#### Listar Clientes

```http
GET /api/mobile.php?action=clientes&search=Marcelo&limit=10
```

### WebSocket

Para notificaciones en tiempo real:

```javascript
// Conectar al servidor WebSocket
const ws = new WebSocket('ws://localhost:8080');

// Escuchar notificaciones
ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Notificación:', data);
};
```

---

## Seguridad

### Medidas Implementadas

#### Autenticación

- Contraseñas hasheadas con bcrypt
- Autenticación de dos factores (2FA)
- Tiempo de sesión configurable
- Bloqueo por intentos fallidos

#### Protección contra Ataques

| Protección | Implementación |
|-----------|---------------|
| SQL Injection | Prepared statements |
| XSS | htmlspecialchars() en outputs |
| CSRF | Tokens en formularios |
| Sesión | HttpOnly cookies |

#### Recomendaciones de Seguridad

1. **Cambiar credenciales por defecto**
2. **Usar HTTPS en producción**
3. **Mantener PHP actualizado**
4. **Limitar acceso a panel admin**
5. **Respaldar base de datos regularmente**

### Permisos de Usuarios

| Rol | Permisos |
|-----|----------|
| admin | Acceso completo |
| técnico | Órdenes, equipos, inventario |
| ventas | Clientes, presupuestos, cotizaciones |

---

## Mantenimiento

### Respaldo de Base de Datos

```bash
# Exportar usando mysqldump
mysqldump -u root -p sat_db > respaldo_$(date +%Y%m%d).sql
```

### Importar Respaldo

```bash
mysql -u root -p sat_db < respaldo_20240423.sql
```

### Limpiar Archivos Temporales

```bash
# En el directorio del proyecto
rm -rf temp/*
rm -rf cache/*
```

### Actualizar Sistema

```bash
# Actualizar dependencias
composer update

# Sincronizar con Git
git pull origin main
```

---

## Solución de Problemas

### Errores Comunes

#### "Error de conexión a la base de datos"

**Solución:**
1. Verificar que MySQL esté corriendo
2. Revisar credenciales en `config/database.php`
3. Verificar que la base de datos exista

#### "Clase mysqli no encontrada"

**Solución:**
1. Habilitar extensión mysqli en php.ini
2. Buscar: `;extension=mysqli`
3. Cambiar a: `extension=mysqli`
4. Reiniciar Apache

#### "SMTP connect() failed"

**Solución:**
1. Verificar credenciales SMTP
2. Para Gmail: usar App Password
3. Verificar que SMTP permita conexiones externas
4. Revisar puerto (587 para TLS, 465 para SSL)

#### "Session already started"

**Solución:**
1. Revisar que no haya múltiples `session_start()`
2. Verificar que no haya output antes de session_start()

#### "Archivo no encontrado" (404)

**Solución:**
1. Verificar configuración de Apache
2. Habilitar mod_rewrite
3. Revisar archivo .htaccess

### Logs

Los errores se registran en:
- PHP error_log
- Consola del navegador
- Archivo `logs/error.log` (si está configurado)

### Soporte

Para reportar errores o sugerencias:
- GitHub Issues
- Email del administrador

---

## Glosario

| Término | Descripción |
|---------|-------------|
| Orden de Servicio | Documento que registra una reparación |
| IGV | Impuesto General a las Ventas (18% en Perú) |
| TCPDF | Biblioteca PHP para generar PDFs |
| PHPMailer | Biblioteca PHP para enviar emails |
| Ratchet | Biblioteca PHP para WebSockets |
| CSRF | Cross-Site Request Forgery |
| XSS | Cross-Site Scripting |
| SQL Injection | Inyección de código SQL |

---

## Créditos

- Desarrollado para talleres de servicio técnico
- Basado en PHP 8 y MySQL 8
- UI con Bootstrap 5
- PDF con TCPDF
- Email con PHPMailer

---

**Versión**: 1.0.0  
**Fecha de creación**: Abril 2026  
**Última actualización**: Abril 2026

---

*Este documento proporciona una guía completa para la instalación, configuración y uso del Sistema SAT.*