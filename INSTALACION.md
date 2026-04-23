# =============================================================================
# COMANDOS PARA INSTALAR Y CONFIGURAR SISTEMA SAT
# =============================================================================

# 1. INSTALAR DEPENDENCIAS COMPOSER (desde la raíz del proyecto)
composer install

# 2. VERIFICAR QUE WAMP TENGA:
#    - PHP 8.x activado
#    - Extensión mysqli habilitada
#    - Extensión openssl habilitada (para PHPMailer)
#    - Extensión mbstring habilitada

# 3. CONFIGURAR BASE DE DATOS EN phpMyAdmin:
#    a) Crear base de datos: sat_db
#    b) Importar archivo: sql/rearme_completo.sql

# 4. VERIFICAR CONFIGURACIÓN DE BASE DE DATOS
#    Editar config/database.php si es necesario:
#    - DB_HOST: localhost
#    - DB_USER: root
#    - DB_PASS: (tu contraseña)
#    - DB_NAME: sat_db

# 5. CONFIGURAR CORREO SMTP (opcional para emails)
#    Acceder desde el navegador:
#    http://localhost/SistemaSAT/configuracion/email.php
#    
#    Configurar con tus datos de Gmail:
#    - SMTP Host: smtp.gmail.com
#    - SMTP Port: 587
#    - SMTP User: tuemail@gmail.com
#    - SMTP Pass: tu_app_password (no tu contraseña normal)
#    - SMTP Secure: tls
#    
#    NOTA: Para Gmail necesitas una "App Password":
#    - Ve a mi cuenta Google > Seguridad
#    - Verificación en 2 pasos > Activar
#    - Buscar "App Passwords" > Crear
#    - Usar esa contraseña de 16 caracteres

# 6. VERIFICAR RUTAS
#    El sistema debe estar en: C:\wamp64\www\SistemaSAT
#    Acceso desde: http://localhost/SistemaSAT/

# 7. CREDENCIALES DE ACCESO
#    Usuario: admin@sat.com
#    Contraseña: admin123

# =============================================================================
# RESUMEN DE COMANDOS (PowerShell)
# =============================================================================

# Desde el directorio del proyecto:
cd C:\wamp64\www\SistemaSAT

# Instalar dependencias
composer install

# Verificar que composer esté instalado
composer --version

# =============================================================================
# SI HAY ERRORES COMUNES:
# =============================================================================

# Error: "PHP Fatal error: Uncaught Error: Class 'mysqli' not found"
# Solución: Habilitar extension=mysqli en php.ini

# Error: "Connection refused" en MySQL"
# Solución: Asegurarse que MySQL esté corriendo en WAMP

# Error: "SMTP connect() failed"
# Solución: Verificar credenciales SMTP y que 2FA esté activo en Gmail

# Error: "Permission denied" en archivos
# Solución: Dar permisos de escritura a carpetas temp, logs, uploads

# =============================================================================
# VERIFICACIÓN FINAL
# =============================================================================

# Probar estos enlaces:
# - Dashboard: http://localhost/SistemaSAT/dashboard/index.php
# - Login: http://localhost/SistemaSAT/autenticacion/login.php
# - Chat: http://localhost/SistemaSAT/chat/index.php (después de login)

# Credenciales:
# Email: admin@sat.com
# Password: admin123