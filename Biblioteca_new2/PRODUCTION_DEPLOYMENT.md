# 🚀 Guía de Despliegue en Producción - Biblioteca TECBA

## 📋 Pre-requisitos

### **🌐 Requisitos del Servidor**
- ✅ **PHP 7.4+** con extensiones: PDO, PDO_MySQL, cURL, JSON, mbstring
- ✅ **MySQL 5.7+** o MariaDB 10.2+
- ✅ **Apache** con mod_rewrite habilitado
- ✅ **SSL Certificate** (HTTPS obligatorio)
- ✅ **Redis** (opcional pero recomendado para caché)

---

## 🔧 Configuración de Base de Datos

### **1. Crear Base de Datos en cPanel**
1. **Acceder a cPanel** → **Databases** → **MySQL Databases**
2. **Crear nueva base de datos**:
   - **Database name**: `bibliotec_main` (o el que prefieras)
   - **Collation**: `utf8mb4_unicode_ci`

3. **Crear usuario de base de datos**:
   - **Username**: `bibliotec_user`
   - **Password**: Generar contraseña segura   3p8}{Jd70£3liSI_(d2=
   - **Host**: `localhost`

4. **Asignar privilegios**:
   - Seleccionar usuario y base de datos
   - Marcar **ALL PRIVILEGES**
   - Click en **Make Changes**

### **2. Importar Estructura SQL**
1. **Acceder a phpMyAdmin** desde cPanel
2. **Seleccionar la base de datos** creada
3. **Click en "Import"**
4. **Seleccionar archivo**: `biblioteca_tecba.sql`
5. **Configuración de importación**:
   - **Character set**: `utf8mb4`
   - **SQL compatibility mode**: `NONE`
6. **Click en "Go"**

---

## 📝 Configuración de Archivos

### **1. Configurar Conexión a Base de Datos**

**Editar `includes/database.php`:**

```php
<?php
// Configuración de producción
$host = 'localhost';
$db   = 'bibliotec_main';        // Nombre de tu BD en cPanel
$user = 'bibliotec_user';       // Usuario creado en cPanel
$pass = 'TU_PASSWORD_SEGURO';  // Contraseña generada
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
```

### **2. Configurar Variables de Entorno**

**Crear archivo `.env`:**

```bash
# Configuración de la API de Gemini AI
GEMINI_API_KEY=tu_api_key_real_aqui

# Configuración de Redis (opcional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_PREFIX=ai_cache:

# Configuración de base de datos (opcional - ya está en database.php)
DB_HOST=localhost
DB_NAME=bibliotec_main
DB_USER=bibliotec_user
DB_PASS=TU_PASSWORD_SEGURO

# Configuración del sistema
AI_TEMPERATURE=0.7
AI_TOP_P=0.8
AI_TOP_K=40
AI_MAX_OUTPUT_TOKENS=32768
```

### **3. Configurar Dominio y URLs**

**Verificar en archivos principales:**

**`includes/header.php`:**
```php
<base href="https://bibliotecba.com/">
```

**`config.php` (si existe):**
```php
define('SITE_URL', 'https://bibliotecba.com/');
define('ASSETS_URL', 'https://bibliotecba.com/images/');
```

---

## 📁 Subida de Archivos al Servidor

### **1. Método 1: cPanel File Manager**

1. **Acceder a cPanel** → **File Manager**
2. **Navegar a `public_html`**
3. **Crear carpeta**: `biblioteca` (o subir directamente a `public_html`)
4. **Subir archivos**:
   - Seleccionar todos los archivos locales
   - Arrastrar o usar "Upload"
   - Esperar a que complete la subida

### **2. Método 2: FTP/SFTP**

**Usar FileZilla o similar:**
```
Host: bibliotecba.com
Port: 21 (FTP) o 22 (SFTP)
Username: tu_usuario_cpanel
Password: tu_password_cpanel
Remote directory: public_html/
```

---

## 🔐 Configuración de Seguridad

### **1. Permisos de Archivos**

**Ejecutar estos comandos en cPanel Terminal:**
```bash
# Permisos correctos para carpetas
find public_html -type d -exec chmod 755 {} \;

# Permisos correctos para archivos
find public_html -type f -exec chmod 644 {} \;

# Permisos especiales para carpetas que necesitan escritura
chmod 755 public_html/cache/
chmod 755 public_html/images/
```

### **2. Archivo .htaccess**

**Crear/editar `.htaccess` en la raíz:**
```apache
# Seguridad básica
Options -Indexes
ServerSignature Off

# Prevenir acceso a archivos sensibles
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.sql">
    Order allow,deny
    Deny from all
</Files>

# URLs amigables
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Forzar HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST%{REQUEST_URI} [L,R=301]

# Headers de seguridad
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

---

## 🤖 Configuración del Asistente IA

### **1. Obtener API Key de Gemini AI**

1. **Ir a**: https://makersuite.google.com/app/apikey
2. **Iniciar sesión** con cuenta de Google
3. **Crear nueva API Key**
4. **Copiar la key** y guardar en `.env`

### **2. Configurar Redis (Opcional pero recomendado)**

**En cPanel:**
1. **Setup Redis Manager** (si está disponible)
2. **Crear instancia Redis**
3. **Obtener credenciales** y configurar en `.env`

---

## 🧪 Verificación y Testing

### **1. Verificar Conexión a BD**

**Crear archivo `test_connection.php`:**
```php
<?php
require_once 'includes/database.php';
if ($pdo) {
    echo "✅ Conexión a base de datos exitosa";
} else {
    echo "❌ Error en conexión a base de datos";
}
?>
```

### **2. Verificar Funcionamiento del Sistema**

1. **Acceder a**: `https://bibliotecba.com/`
2. **Probar registro** de nuevo usuario
3. **Probar login** con usuario existente
4. **Probar el asistente IA**
5. **Verificar que todas las páginas funcionen**

---

## 🔧 Post-Instalación

### **1. Ejecutar Scripts de Base de Datos**

**Acceder a phpMyAdmin y ejecutar:**
```sql
-- Ejecutar database/create_ai_role_limits.sql
-- Esto creará las tablas necesarias para el sistema IA
```

### **2. Configurar Primer Usuario Admin**

1. **Registrar usuario** normal desde la web
2. **Acceder a phpMyAdmin**
3. **Actualizar rol** a 'Super Admin':
```sql
UPDATE usuarios SET rol = 'Super Admin' WHERE email = 'admin@bibliotecba.com';
```

### **3. Limpiar Archivos Temporales**

**Eliminar archivos de prueba:**
- `test_connection.php`
- Cualquier archivo de desarrollo

---

## 📊 Monitoreo y Mantenimiento

### **1. Logs del Sistema**

**Revisar regularmente:**
- **Logs de errores de PHP** en cPanel → **Errors**
- **Logs de Apache** en cPanel → **Metrics** → **Raw Access**
- **Logs del asistente IA** en la base de datos `ai_usage_logs`

### **2. Backups Automáticos**

**Configurar en cPanel:**
1. **Backup Wizard** → **Full Backup**
2. **Programar backups** diarios/semanales
3. **Guardar en ubicación segura** (Google Drive, Dropbox, etc.)

---

## ⚠️ Troubleshooting Común

### **1. Error 500 - Internal Server Error**
```bash
# Revisar logs de errores en cPanel
# Verificar permisos de archivos (755 carpetas, 644 archivos)
# Comprobar sintaxis PHP en archivos modificados
```

### **2. Error de Conexión a BD**
```php
// Verificar credenciales en includes/database.php
// Comprobar que el usuario tenga privilegios en cPanel
// Verificar que el nombre de la BD sea correcto
```

### **3. El Asistente IA no funciona**
```bash
# Verificar API Key en .env
# Comprobar que cURL esté habilitado
# Revisar logs de errores para ver mensajes de Gemini API
```

---

## 🎯 Checklist Final de Producción

### **✅ Antes de Lanzar:**

- [ ] **Base de datos creada e importada**
- [ ] **Conexión a BD funcionando**
- [ ] **Archivo .env configurado con API key real**
- [ ] **Permisos de archivos correctos**
- [ ] **HTTPS funcionando con certificado válido**
- [ ] **Asistente IA operativo**
- [ ] **Usuario Super Admin configurado**
- [ ] **Backups automáticos configurados**
- [ ] **Todos los formularios funcionando**
- [ ] **Sistema de caché operativo (Redis o archivos)**
- [ ] **Logs de errores revisados**

---

## 📞 Soporte

### **🔗 Recursos Útiles:**
- **Documentación completa**: `DOCUMENTACION_SISTEMA.md`
- **Guía IA**: Sección 🤖 Sistema de Asistente Inteligente
- **cPanel Documentation**: https://docs.cpanel.net/
- **PHP Documentation**: https://www.php.net/docs.php

### **🚨 Si tienes problemas:**
1. **Revisar logs de errores** en cPanel
2. **Verificar configuración** de archivos `.env` y `database.php`
3. **Comprobar permisos** de archivos y carpetas
4. **Probar componentes** individualmente

---

**🎉 ¡Felicidades! Tu Biblioteca TECBA está ahora en producción**

*Última actualización: Marzo 2026*
*Versión: 1.0.0 - Producción*
