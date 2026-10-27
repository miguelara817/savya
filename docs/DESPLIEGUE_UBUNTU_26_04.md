# Despliegue de SAVYA en Ubuntu Server 26.04

Guía para instalar la aplicación y PostgreSQL directamente en una VM Ubuntu, sin contenedores. Los ejemplos usan Nginx, PHP-FPM, PostgreSQL 17 y un dominio de ejemplo. Sustituye `TU-DOMINIO`, `ORGANIZACION/REPOSITORIO`, el usuario de Git y el socket de PHP por los valores reales.

## 1. Componentes y requisitos del proyecto

- Laravel 13 y PHP `^8.4.27`. PHP 8.5 es compatible con esa restricción de Composer; comprueba la versión que ofrece Ubuntu 26.04 antes de instalar.
- El `docker-compose.yaml` del proyecto usa PostgreSQL 17. Para mantener la paridad con desarrollo, esta guía instala PostgreSQL 17 desde el repositorio oficial PGDG. Es una instalación nativa, no un contenedor.
- Vite 8.3.5 requiere Node `^20.19.0` o `>=22.12.0`.
- El proyecto guarda sesiones, caché y cola en la base de datos por defecto. Laravel crea las tablas correspondientes con sus migraciones.
- `public/build` está excluido de Git; hay que generarlo durante cada publicación o incluirlo como artefacto de compilación.
- El inicio de sesión exige MFA por correo. Configura un SMTP real antes de probar el acceso.

## 2. Preparar Ubuntu, DNS y firewall

Configura un registro DNS `A` —y `AAAA` solo si la VM tiene IPv6 pública funcional— para que el dominio resuelva a la VM. Actualiza e instala las herramientas básicas:

```bash
sudo apt update
sudo apt full-upgrade -y
sudo apt install -y nginx git curl ca-certificates unzip acl ufw
```

Permite SSH antes de habilitar UFW para no cerrar la sesión administrativa. Restringe SSH a las IP necesarias cuando sea posible:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status
```

Abre también esos puertos en el firewall de red del proveedor. **No abras el puerto 5432 de PostgreSQL a Internet.**

## 3. Instalar PostgreSQL 17 en el sistema

Ubuntu 26.04 incluye una versión de PostgreSQL en sus repositorios. Para usar PostgreSQL 17, como en desarrollo, instala el repositorio oficial PGDG y los paquetes versionados:

```bash
sudo apt install -y postgresql-common
sudo /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh
sudo apt update
sudo apt install -y postgresql-17 postgresql-client-17
sudo systemctl enable --now postgresql
```

Crea un rol y una base exclusivos para la aplicación. `createuser` solicitará una contraseña de forma interactiva:

```bash
sudo -u postgres createuser --pwprompt savya_app
sudo -u postgres createdb --owner=savya_app savya_prod
```

PostgreSQL debe escuchar solo en localhost para esta arquitectura. No modifiques `listen_addresses` para exponer la base en una interfaz pública.

Si prefieres la versión incluida por Ubuntu en vez de PostgreSQL 17, instala `postgresql` y `postgresql-client`, y confirma la versión con `psql --version`.

## 4. Instalar PHP, Composer y extensiones

Consulta primero las versiones que ofrecen los repositorios del sistema:

```bash
apt-cache policy php-cli php-fpm php-pgsql
```

Instala PHP CLI/FPM, el driver PostgreSQL y las extensiones habituales del proyecto:

```bash
sudo apt install -y \
  php-cli php-fpm php-pgsql php-mbstring php-xml php-curl \
  php-zip php-bcmath php-intl php-gd php-opcache composer
```

Si compilas los assets de Vite en la VM, instala Node/npm y verifica la versión:

```bash
sudo apt install -y nodejs npm
node -v
npm -v
```

Si Node es anterior a `20.19.0` y también anterior a `22.12.0`, compila en CI con una versión compatible o instala una versión mantenida desde la distribución oficial de Node.js.

Comprueba PHP, las extensiones y Composer:

```bash
php -v
php -m | grep -E 'pdo_pgsql|pgsql|mbstring|xml|curl|zip|intl'
composer --version
```

Después de instalar las dependencias del proyecto, verifica los requisitos de producción:

```bash
composer check-platform-reqs --no-dev
```

Comprueba qué socket usa PHP-FPM; se usará en la configuración de Nginx:

```bash
ls /run/php/
```

Los comprobantes de pago admiten archivos de hasta 10 MB. En el `php.ini` de FPM configura, como mínimo:

```ini
upload_max_filesize = 15M
post_max_size = 20M
```

Reinicia el servicio FPM de la versión instalada después de modificarlo; por ejemplo, `sudo systemctl restart php8.5-fpm`.

## 5. Crear usuario y directorios de despliegue

Crea un usuario sin permisos de `sudo` para publicar los releases y añádelo al grupo de Nginx/PHP-FPM:

```bash
sudo adduser deploy
sudo usermod -aG www-data deploy
```

Cierra y vuelve a abrir la sesión de `deploy` para que se aplique el grupo. Prepara un directorio por release y otro compartido para configuración y archivos persistentes:

```bash
sudo install -d -o deploy -g www-data -m 2750 \
  /var/www/savya/releases \
  /var/www/savya/shared

sudo install -d -o deploy -g www-data -m 2770 \
  /var/www/savya/shared/storage
```

El proceso web debe poder leer el código y escribir en `storage` y `bootstrap/cache`; no le des permisos de escritura sobre todo el código.

## 6. Obtener el código y configurar `.env`

Clona una nueva versión del repositorio como `deploy`, usando una llave SSH de solo lectura si el repositorio es privado:

```bash
sudo -iu deploy
cd /var/www/savya

RELEASE="$(date -u +%Y%m%d%H%M%S)"
RELEASE_PATH="/var/www/savya/releases/$RELEASE"
git clone --depth 1 --branch main \
  git@github.com:ORGANIZACION/REPOSITORIO.git "$RELEASE_PATH"

cp "$RELEASE_PATH/.env.example" /var/www/savya/shared/.env
ln -s /var/www/savya/shared/.env "$RELEASE_PATH/.env"
ln -s /var/www/savya/shared/storage "$RELEASE_PATH/storage"

mkdir -p /var/www/savya/shared/storage/app/private \
  /var/www/savya/shared/storage/app/public \
  /var/www/savya/shared/storage/framework/cache/data \
  /var/www/savya/shared/storage/framework/sessions \
  /var/www/savya/shared/storage/framework/views \
  /var/www/savya/shared/storage/logs
```

**Importante:** `storage` debe ser un enlace simbólico, no un directorio creado dentro del release. El comando anterior fallará si `$RELEASE_PATH/storage` ya existe como directorio. Comprueba el resultado antes de continuar:

```bash
ls -ld "$RELEASE_PATH/storage"
readlink -f "$RELEASE_PATH/storage"
```

La segunda salida debe ser `/var/www/savya/shared/storage`. En una instalación nueva, crea el enlace antes de ejecutar Artisan o aceptar cargas de archivos. Si se desplegó inicialmente sin el enlace y no hay archivos que conservar, un administrador puede sustituir el directorio local por el enlace así:

```bash
sudo rm -rf -- "$RELEASE_PATH/storage"
sudo -u deploy ln -s /var/www/savya/shared/storage "$RELEASE_PATH/storage"
```

No ejecutes el borrado si necesitas conservar archivos. Cópialos primero a `shared/storage` y confirma que quedaron en el destino correcto. Si no tienes permisos `sudo`, solicita a un administrador que realice la corrección. `sudo -u deploy` por sí solo no eleva permisos: ejecuta el comando como el usuario `deploy`.

El disco `public` de Laravel tiene como raíz `storage/app/public`; por tanto, las imágenes de productos guardadas en el disco público quedan en `shared/storage/app/public/productos`. El enlace web debe apuntar a esa raíz (no a `shared/storage`):

```bash
ln -sfn /var/www/savya/shared/storage/app/public "$RELEASE_PATH/public/storage"
readlink -f "$RELEASE_PATH/public/storage"
```

La última salida debe ser `/var/www/savya/shared/storage/app/public`. Crea las carpetas persistentes dentro de `shared/storage`, no dentro del release.

Edita `/var/www/savya/shared/.env`. Cambia SQLite por PostgreSQL y deja los valores de producción:

```dotenv
APP_NAME=Savya
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU-DOMINIO
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_TIMEZONE=America/La_Paz
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=savya_prod
DB_USERNAME=savya_app
DB_PASSWORD="CONTRASEÑA_DE_POSTGRESQL"

SESSION_DRIVER=database
SESSION_ENCRYPT=true
CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=120
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=SERVIDOR_SMTP
MAIL_PORT=587
MAIL_USERNAME=USUARIO_SMTP
MAIL_PASSWORD="CONTRASEÑA_SMTP"
MAIL_FROM_ADDRESS=CORREO_REMITENTE
MAIL_FROM_NAME="${APP_NAME}"
```

Adapta SMTP al proveedor; por ejemplo, algunos utilizan `smtps` en el puerto 465. El correo debe funcionar antes del primer inicio de sesión porque el MFA envía un código a la dirección del usuario. Elimina también de `.env` las líneas `ADMIN_NAME`, `ADMIN_EMAIL` y `ADMIN_PASSWORD` copiadas del ejemplo; las credenciales iniciales se pasarán una sola vez al seeder mediante variables de entorno.

Protege el archivo de entorno:

```bash
chgrp www-data /var/www/savya/shared/.env
chmod 640 /var/www/savya/shared/.env
```

Genera `APP_KEY` una sola vez, una vez configurado el `.env`:

```bash
cd "$RELEASE_PATH"
php artisan key:generate --force
```

Conserva esa clave en todas las publicaciones y respaldos; no la regeneres durante una actualización.

## 7. Instalar dependencias y compilar assets

Desde el directorio del release:

```bash
cd "$RELEASE_PATH"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
```

El directorio `public/build` no se versiona. Compílalo en una máquina/CI con una versión compatible de Node y copia el resultado al release, o compílalo en la VM:

```bash
node -v
npm ci
npm run build
```

Node debe ser `20.19.0` o posterior compatible con Vite 8. Si el paquete `nodejs` de Ubuntu es más antiguo, compila los assets en CI con Node 22.12+ o Node 24 y despliega también `public/build`.

El proyecto guarda archivos subidos con el disco `public`, cuya raíz es `storage/app/public`; las imágenes de productos quedan en `storage/app/public/productos`. El enlace `storage` del release apunta a `shared/storage`, por lo que este directorio es persistente entre publicaciones. Enlaza `public/storage` a la raíz real del disco público para que las URL `/storage/...` sigan funcionando aunque se retiren releases antiguos. El primer enlace debe haberse creado en el paso 6; compruébalo antes de continuar:

```bash
ln -sfn /var/www/savya/shared/storage/app/public "$RELEASE_PATH/public/storage"
readlink -f "$RELEASE_PATH/storage"
readlink -f "$RELEASE_PATH/public/storage"
```

Las salidas esperadas son `/var/www/savya/shared/storage` y `/var/www/savya/shared/storage/app/public`, respectivamente. No configures `public/storage` hacia `shared/storage` directamente: ese nivel contiene `app/public`, no los archivos del disco público. La ruta correcta para productos es `/var/www/savya/shared/storage/app/public/productos`.

## 8. Migraciones, permisos y cuenta administradora

Ejecuta las migraciones:

```bash
php artisan migrate --force
```

Carga solo los datos funcionales requeridos:

```bash
php artisan db:seed --class=PersonaTipoSeeder --force
php artisan db:seed --class=OrdenEstadoSeeder --force
php artisan db:seed --class=ShieldPermisosSeeder --force
php artisan db:seed --class=RolesPermisosSeeder --force
```

**No ejecutes `DatabaseSeeder` en producción:** llama a `UsersPruebasSeeder`, que crea usuarios de prueba con contraseñas conocidas, además de datos de almacenes de ejemplo.

El `AdminUserSeeder` tiene valores predeterminados inseguros. Para la primera cuenta, introduce la contraseña de forma interactiva en la sesión SSH y evita guardarla en el historial:

```bash
read -rsp 'Contraseña inicial del administrador: ' ADMIN_PASSWORD
printf '\n'
export ADMIN_PASSWORD
export ADMIN_EMAIL='admin@TU-DOMINIO'
export ADMIN_NAME='Administrador'
php artisan db:seed --class=AdminUserSeeder --force
unset ADMIN_PASSWORD ADMIN_EMAIL ADMIN_NAME
```

Configura antes el SMTP y asegúrate de que `ADMIN_EMAIL` sea una dirección funcional para recibir el código MFA. Crea los almacenes reales desde la aplicación; ejecuta `AlmacenSeeder` solo si sus ubicaciones coinciden con tu operación.

Mantén abierta la sesión `deploy` para los comandos de la aplicación y usa una segunda sesión administrativa para UFW, Nginx, Certbot y systemd. Composer, Artisan, Node y Git se ejecutan como `deploy`; los cambios en `/etc` y `systemctl` requieren la cuenta administradora.

## 9. Configurar Nginx

Los cambios de Nginx y systemd requieren una cuenta con `sudo`. Puedes mantener una segunda sesión administrativa abierta mientras `deploy` publica la aplicación.

Crea `/etc/nginx/sites-available/savya` y reemplaza el dominio y el socket FPM por los valores reales:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name TU-DOMINIO;

    root /var/www/savya/current/public;
    index index.php;
    charset utf-8;
    client_max_body_size 20m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activa el sitio y valida la sintaxis:

```bash
sudo ln -s /etc/nginx/sites-available/savya /etc/nginx/sites-enabled/savya
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
```

Deja el sitio configurado y espera a activar el primer release antes de recargar Nginx.

## 10. Permisos, cachés y activación del release

Ejecuta estos permisos como `deploy`, propietario de los releases. Al estar en el grupo `www-data`, puedes dejar a PHP-FPM acceso de grupo:

```bash
chgrp -R www-data /var/www/savya/shared/storage "$RELEASE_PATH/bootstrap/cache"
chmod -R g+rwX /var/www/savya/shared/storage "$RELEASE_PATH/bootstrap/cache"
find /var/www/savya/shared/storage "$RELEASE_PATH/bootstrap/cache" -type d -exec chmod g+s {} \;
```

Genera las cachés de producción y activa el release mediante un cambio de enlace:

```bash
cd "$RELEASE_PATH"
php artisan optimize

ln -sfn "$RELEASE_PATH" /var/www/savya/current.next
mv -Tf /var/www/savya/current.next /var/www/savya/current
```

Como administrador, recarga Nginx y PHP-FPM; reemplaza el nombre de unidad por la versión instalada:

```bash
sudo systemctl reload php8.5-fpm
sudo systemctl reload nginx
```

Cuando el DNS apunte a la VM y el puerto 80 sea accesible, instala Certbot y solicita HTTPS:

```bash
sudo snap install --classic certbot
sudo certbot --nginx -d TU-DOMINIO
sudo certbot renew --dry-run
```

## 11. Worker de cola con systemd

Como administrador, crea `/etc/systemd/system/savya-worker.service`. La cola predeterminada es `database`, así que este servicio procesa trabajos pendientes:

```ini
[Unit]
Description=Savya Laravel Queue Worker
After=network.target postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/savya/current
ExecStart=/usr/bin/php /var/www/savya/current/artisan queue:work database --sleep=3 --tries=3 --timeout=90
Restart=always
RestartSec=5
TimeoutStopSec=150

[Install]
WantedBy=multi-user.target
```

Verifica que `/usr/bin/php` sea la versión compatible con Composer; si no, usa la ruta absoluta de la versión instalada. Habilita el worker:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now savya-worker
sudo systemctl status savya-worker
```

En cada publicación, reinicia el worker para que lea el código nuevo:

```bash
# Como deploy:
cd /var/www/savya/current
php artisan queue:restart
```

```bash
# Como administrador:
sudo systemctl restart savya-worker
```

Actualmente el proyecto no declara tareas programadas. Si se agregan, configura `php artisan schedule:run` cada minuto mediante cron o un timer de systemd.

## 12. Verificar la aplicación

```bash
# Como deploy:
cd /var/www/savya/current
php artisan about
php artisan migrate:status
curl -I https://TU-DOMINIO/up
```

```bash
# Como administrador:
sudo systemctl status nginx postgresql savya-worker
```

Revisa logs si algo falla:

```bash
sudo journalctl -u nginx -n 100 --no-pager
sudo journalctl -u savya-worker -n 100 --no-pager
```

Antes de anunciar el sistema, inicia sesión con el administrador, completa el desafío MFA y prueba el envío SMTP de correos.

## 13. Publicaciones futuras

Para cada versión:

1. Crea un nuevo directorio en `releases` y clona el commit/tag que vas a publicar.
2. Enlaza `shared/.env` y `shared/storage` al release, y enlaza `public/storage` a `/var/www/savya/shared/storage/app/public`.
3. Ejecuta `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader` y compila/copia `public/build`.
4. Haz un respaldo antes de migraciones que cambien o eliminen datos.
5. Ejecuta `php artisan migrate --force` y `php artisan optimize`.
6. Cambia atómicamente el enlace `current` y recarga PHP-FPM/Nginx.
7. Ejecuta `php artisan queue:restart` y reinicia `savya-worker`.
8. Comprueba `https://TU-DOMINIO/up`, el login MFA, los archivos y los registros.

Conserva los releases anteriores para poder apuntar `current` a la versión previa. Una reversión de código no siempre revierte una migración de base de datos.

## 14. Respaldos

Respalda PostgreSQL y el directorio compartido de almacenamiento; los comprobantes se guardan como archivos y no solo en la base de datos. Un respaldo en el mismo disco no protege ante la pérdida de la VM: copia los archivos a otra máquina o almacenamiento externo y prueba su restauración.

Ejemplo de volcado PostgreSQL en un directorio privado:

```bash
sudo install -d -o postgres -g postgres -m 0700 /var/backups/savya
sudo -u postgres sh -c 'pg_dump -Fc savya_prod > /var/backups/savya/savya_$(date +%F).dump'
```

Respalda también `/var/www/savya/shared/storage` y conserva de forma segura el `.env`, especialmente `APP_KEY` y las credenciales SMTP/DB.

## Referencias

- [Laravel 13: despliegue](https://laravel.com/docs/13.x/deployment)
- [PostgreSQL Apt Repository para Ubuntu](https://www.postgresql.org/download/linux/ubuntu/)
- [Ubuntu Server: PostgreSQL](https://documentation.ubuntu.com/server/how-to/databases/install-postgresql/)
- [Ubuntu Server: Nginx](https://documentation.ubuntu.com/server/how-to/web-services/install-nginx/)
- [Ubuntu Server: TLS con Let's Encrypt](https://documentation.ubuntu.com/server/how-to/security/obtain-tls-certificates/)
