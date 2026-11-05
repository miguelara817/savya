# Despliegue de SAVYA con Docker en Ubuntu Server

Guía complementaria a [DESPLIEGUE_UBUNTU_26_04.md](DESPLIEGUE_UBUNTU_26_04.md): Docker reemplaza la instalación nativa de PHP, Nginx, worker y PostgreSQL. La imagen contiene PHP-FPM, Nginx, assets Vite y el worker; PostgreSQL corre en un contenedor separado. Docker Compose administra ambos y los volúmenes persisten datos.

## Requisitos y arquitectura

Ubuntu Server 26.04, dominio apuntando al servidor, Docker Engine con el plugin Compose, y puertos 22/80/443 permitidos en UFW y firewall del proveedor. No publiques el puerto 5432. El puerto HTTP de la aplicación queda enlazado solo a `127.0.0.1:8080`; instala un proxy TLS (por ejemplo Nginx/Certbot en el host) que reenvíe al puerto local.

## Instalar Docker

Instala Docker Engine y Compose siguiendo la [documentación oficial de Docker para Ubuntu](https://docs.docker.com/engine/install/ubuntu/). Verifica:

```bash
docker --version
docker compose version
```

Usa `sudo` en los comandos Docker si tu usuario no pertenece al grupo `docker` (ese grupo equivale a privilegios de root).

## Obtener el código y preparar configuración

```bash
sudo mkdir -p /opt/savya
sudo chown "$USER":"$USER" /opt/savya
cd /opt/savya
git clone --depth 1 --branch main git@github.com:ORGANIZACION/REPOSITORIO.git .
cp .env.example .env
```

Edita `.env` con secretos reales. Configura al menos:

```dotenv
APP_NAME=Savya
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU-DOMINIO
APP_KEY=
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=savya_prod
DB_USERNAME=savya_app
DB_PASSWORD="CONTRASEÑA_LARGA_ALEATORIA"
SESSION_DRIVER=database
SESSION_ENCRYPT=true
CACHE_STORE=database
QUEUE_CONNECTION=database
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

Confirma que `ADMIN_NAME`, `ADMIN_EMAIL` y `ADMIN_PASSWORD` no queden con valores inseguros del ejemplo. Protege `.env` (`chmod 600 .env`) y no lo subas al repositorio. SMTP real es necesario para el MFA por correo. El volumen `app_storage` conserva archivos subidos; `postgres_data` conserva la base.

## Construir e iniciar

El Dockerfile construye assets con Node 22, dependencias PHP sin paquetes de desarrollo y una imagen PHP 8.4 con PostgreSQL. No copia el `.env` a la imagen.

```bash
docker compose -f docker-compose.prod.yaml build --pull
```

Genera la clave una sola vez y guárdala persistentemente en `.env`:

```bash
docker compose -f docker-compose.prod.yaml run --rm app php artisan key:generate --show
```

Copia la clave `base64:...` resultante en `APP_KEY`, luego arranca:

```bash
docker compose -f docker-compose.prod.yaml up -d
docker compose -f docker-compose.prod.yaml ps
docker compose -f docker-compose.prod.yaml logs -f app
```

El contenedor ejecuta migraciones con `--force`, enlaza el almacenamiento público y optimiza Laravel al arrancar; Supervisor mantiene PHP-FPM, Nginx y `queue:work` activos. La primera migración debe completarse antes de aceptar tráfico. No ejecutes `DatabaseSeeder` en producción, ya que crea usuarios de prueba. Si hacen falta catálogos, sigue los seeders selectivos y la creación inicial de administrador descritos en la guía Ubuntu nativa, adaptando `php artisan` a `docker compose -f docker-compose.prod.yaml exec app php artisan`.

## Proxy HTTPS del host

Configura Nginx del host para el dominio con `client_max_body_size 20m` y proxy a `http://127.0.0.1:8080`; configura Certbot/TLS según la sección Nginx de la guía Ubuntu existente. No expongas directamente el puerto 8080 ni el de PostgreSQL. Comprueba `https://TU-DOMINIO/up` y prueba inicio de sesión, MFA, SMTP y carga/lectura de comprobantes.

## Actualizaciones, operación y respaldos

Desde `/opt/savya`, actualiza el código, reconstruye y recrea el servicio:

```bash
git pull --ff-only
docker compose -f docker-compose.prod.yaml build --pull
docker compose -f docker-compose.prod.yaml up -d
docker compose -f docker-compose.prod.yaml ps
```

Haz respaldo de la base y archivos antes de desplegar cambios de esquema. `docker compose down` conserva los volúmenes; **no uses `down -v`**, pues elimina datos persistentes. Ejemplo de respaldo:

```bash
mkdir -p backups
docker compose -f docker-compose.prod.yaml exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > "backups/savya-$(date +%F).sql"
docker run --rm -v savya_app_storage:/data -v "$PWD/backups:/backup" alpine tar czf /backup/storage-$(date +%F).tgz -C /data .
```

Confirma el nombre real del volumen con `docker volume ls` (Compose suele prefijar el nombre con el proyecto). Copia respaldos a almacenamiento externo y ensaya restauración. Conserva `.env` y especialmente `APP_KEY` con acceso restringido.
