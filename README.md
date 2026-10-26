# SAVYA

Aplicación web para la gestión de órdenes e inventario, construida con Laravel y un panel administrativo Filament.

## Requisitos

- PHP **8.4.27 o superior**, con las extensiones requeridas por Laravel y `pdo_pgsql`.
- Composer 2.
- Node.js y npm compatibles con Vite 8.
- Docker con el plugin Docker Compose.

Versiones declaradas principales: Laravel `^13.17`, Filament `5.10.2`, PHP `^8.4.27`. Consulta `composer.json` y `package.json` para la lista completa.

## Instalación local con Docker Compose

El Compose del proyecto inicia PostgreSQL 17 y Mailpit. La aplicación Laravel se ejecuta en el host (no dentro de Docker), por lo que debe conectarse a PostgreSQL usando `127.0.0.1:5430` y a Mailpit usando `127.0.0.1:1025`.

Desde la raíz del repositorio, inicia los servicios:

```bash
docker compose up -d
```

Comprueba el estado de los contenedores:

```bash
docker compose ps
```

Instala dependencias PHP y JavaScript:

```bash
composer install
npm install
```

Prepara la configuración de Laravel:

```bash
cp .env.example .env
```

En Windows PowerShell, usa en lugar del comando anterior:

```powershell
Copy-Item .env.example .env
```

Edita `.env` para que coincida con PostgreSQL y los servicios de Docker Compose:

```dotenv
APP_NAME=SAVYA
APP_URL=http://localhost:8000
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5430
DB_DATABASE=savyaDB
DB_USERNAME=root
DB_PASSWORD=12345678
ADMIN_NAME=Administrador
ADMIN_EMAIL=admin@correo.bo
ADMIN_PASSWORD=cambia-esta-clave
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_SCHEME=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Genera la clave de la aplicación, ejecuta migraciones y datos iniciales, y compila los recursos frontend:

```bash
php artisan key:generate
php artisan migrate --seed
npm run build
```

El seeder crea el usuario administrador usando `ADMIN_NAME`, `ADMIN_EMAIL` y `ADMIN_PASSWORD` del `.env` y le asigna el rol `super_admin`. **Cambia la contraseña de ejemplo antes de iniciar**. No compartas ni subas el archivo `.env` al repositorio.

## Iniciar la aplicación

### Configuración de archivos temporales en PHP (Windows/Herd)

Livewire necesita que PHP pueda crear archivos temporales tanto al recibir una subida como al procesarla. En instalaciones de Windows con Herd, si al subir un archivo aparecen errores como `unable to create a temporary file` o `stream_get_meta_data(): Argument #1 ($stream) must be of type resource, false given`, configura estas directivas en el `php.ini` de la versión de PHP que usa Herd (por ejemplo, `C:\Users\<usuario>\.config\herd\bin\php84\php.ini`):

```ini
upload_tmp_dir = "C:\\Users\\<usuario>\\AppData\\Local\\Temp"
sys_temp_dir = "C:\\Users\\<usuario>\\AppData\\Local\\Temp"
```

Reemplaza `<usuario>` por el nombre de la cuenta de Windows y confirma que la carpeta existe y permite escritura. Reinicia Herd/PHP para aplicar los cambios. Verifica la configuración activa con `php --ini` y `php -i` (las directivas deben apuntar a la carpeta temporal). Si `php --ini` muestra otro `php.ini`, modifica ese archivo en lugar del ejemplo.

Después, en una terminal:

```bash
php artisan serve
```

### Errores de archivos temporales en Linux

El error también puede ocurrir en Ubuntu o Debian si el directorio temporal configurado para PHP no existe, está lleno o el usuario del proceso web no tiene permisos de escritura. Normalmente PHP usa `/tmp`; no hace falta cambiar `php.ini` si ese directorio funciona.

Comprueba la configuración de la versión de PHP que utiliza el servidor web (no solo la CLI) con `php --ini` y `php -i`, y valida el directorio temporal:

```bash
php --ini
php -i | grep -E '^(upload_tmp_dir|sys_temp_dir|upload_max_filesize|post_max_size)'
ls -ld /tmp
df -h /tmp
```

`/tmp` normalmente debe tener permisos `drwxrwxrwt` (modo `1777`). Si los permisos están incorrectos, un administrador puede restaurarlos con:

```bash
sudo chmod 1777 /tmp
```

Si `/tmp` no está disponible para el servicio PHP, crea un directorio temporal dedicado, asígnalo al usuario que ejecuta PHP-FPM (frecuentemente `www-data` en Ubuntu/Debian) y configúralo en el `php.ini` correspondiente:

```bash
sudo install -d -o www-data -g www-data -m 700 /var/lib/php/uploads
```

```ini
upload_tmp_dir = "/var/lib/php/uploads"
sys_temp_dir = "/var/lib/php/uploads"
```

Reinicia PHP-FPM y el servidor web para aplicar los cambios (por ejemplo, `sudo systemctl restart php8.4-fpm` y, si aplica, `sudo systemctl reload nginx`). Ajusta el nombre del servicio a la versión instalada. En Docker, verifica que el directorio exista y tenga permisos para el usuario del contenedor; los cambios locales a `php.ini` no necesariamente se aplican dentro del contenedor.

Visita `http://localhost:8000/admin` e inicia sesión con el correo y contraseña definidos en `.env`. El panel requiere verificación multifactor por correo. Mailpit recibe los mensajes SMTP en `http://localhost:1025`; abre su interfaz web en **http://localhost:8025** para consultar el código de verificación y otros correos enviados durante el desarrollo.

Para desarrollo frontend con recarga automática, ejecuta en otra terminal:

```bash
npm run dev
```

Si necesitas correr colas (por ejemplo, para correo encolado), inicia también:

```bash
php artisan queue:work
```

## Conexión desde un contenedor

Si ejecutas Laravel dentro de un contenedor que comparte la red de Docker Compose con los servicios, usa el nombre del servicio como host y el puerto interno:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=savyaDB
DB_USERNAME=root
DB_PASSWORD=12345678
MAIL_HOST=mailpit
MAIL_PORT=1025
```

Los puertos publicados al host son PostgreSQL `5430` y SMTP `1025`; Mailpit UI usa el puerto `8025`. Los datos de PostgreSQL y Mailpit persisten en volúmenes Docker. Para detener los servicios sin borrar datos usa `docker compose down`; **no agregues `-v`**, porque eliminaría los volúmenes.

Para reiniciar desde cero intencionalmente, `docker compose down -v` borra esos datos persistidos. No uses ese comando si necesitas conservar información.

## Pruebas

El proyecto usa Pest. Ejecuta todas las pruebas con:

```bash
php artisan test --compact
```

O un archivo específico:

```bash
php artisan test --compact tests/Feature/FlujoInventarioTest.php
```

## Comandos útiles

```bash
php artisan route:list
php artisan migrate:status
php artisan db:seed
php artisan optimize:clear
```

## Estructura relevante

- `app/Filament/Resources`: recursos y pantallas del panel.
- `app/Services`: operaciones de negocio (órdenes, salidas, entregas y correo).
- `app/Models`, `app/Policies`, `app/Enums`: datos, autorización y códigos de dominio.
- `database/migrations`, `database/seeders`: esquema y datos iniciales.
- `tests/Feature`: pruebas de flujos principales.

Para una guía de soporte y mantenimiento más detallada, consulta [`GUIA_MANTENIMIENTO.md`](GUIA_MANTENIMIENTO.md).
