# Poner la plataforma en línea como demostración

GitHub Pages **no sirve** para esto. Pages solo entrega archivos; la
plataforma necesita PHP y MySQL. Lo que hay en
`andretys.github.io/Stellar` es la cara pública —portada, historias y
los 18 artículos de ciencia— y no más.

Para enseñar la plataforma entera hay tres caminos. El segundo es el
que conviene si quieres un enlace que mandar antes de una reunión.

## 1. Tu propia laptop, sin internet

Lo más barato y lo más fiable dentro de un colegio.

```bash
./local.sh          # arranca base y servidor
./local.sh --reset  # la deja como recién instalada
```

Queda en `http://localhost:8000/innovasteam`. En Windows, `local.bat`.

- **A favor:** cero costo, funciona con el wifi del colegio caído, que
  es exactamente el escenario del que te van a preguntar.
- **En contra:** no puedes mandar un enlace. Y si la laptop falla, la
  reunión se acabó.

Llévalo siempre como respaldo aunque tengas el demo en línea.

## 2. Hosting compartido con PHP y MySQL

Entre 15 y 30 soles al mes. Sirve cualquiera que ofrezca **PHP 8.1 o
superior**, **MySQL o MariaDB** y **cron**. Esas tres, y ya.

### Qué subir

Todo el proyecto menos lo que no hace falta en el servidor:

Puedes dejar fuera `movil/`, `electron/`, `_site/` y `.git/`: no se usan
en el servidor. **Sí** tienen que ir `schema.sql`, `seed_data.sql` y la
carpeta `migrations/`, porque son los que usa el reinicio diario.

### Los cuatro pasos

**1. La base de datos.** En el panel del hosting, crea una base y un
usuario. Te va a dar un nombre con prefijo, tipo `u123456_demo`. Apunta
nombre, usuario y contraseña.

**2. La configuración.** Copia el ejemplo y edítalo:

```bash
cp includes/config.local.example.php includes/config.local.php
```

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_demo');   // el nombre que te dio el panel
define('DB_USER', 'u123456_demo');
define('DB_PASS', '...');

// '' si el proyecto está en la raíz del dominio.
// '/innovasteam' si está en una subcarpeta.
define('BASE_URL', '');

// Bloquea cambiar contraseñas y desactivar cuentas de prueba.
define('MODO_DEMO', true);
```

**3. Llenar la base.** Por SSH si lo tienes:

```bash
php herramientas/reiniciar_demo.php
```

Si el hosting no da SSH, define también un token y llámalo por web una
vez:

```php
define('DEMO_TOKEN_REINICIO', '...64 caracteres al azar...');
```

```
https://tudominio.com/herramientas/reiniciar_demo.php?token=...
```

Genera el token con `php -r "echo bin2hex(random_bytes(32));"`. Sin ese
token definido la vía web está cerrada: es un script que borra la base
entera.

**4. El cron.** Una vez al día, de madrugada:

```
0 4 * * *  /usr/bin/php /home/usuario/public_html/herramientas/reiniciar_demo.php
```

Esto es lo que hace que el demo sobreviva. Cualquiera que entre con las
cuentas de prueba puede calificar mal, borrar un aula o subir lo que
sea; a las cuatro de la mañana vuelve a estar entero.

### Comprobar que quedó bien

Entra con `EST-001` y la contraseña `password`. Si ves los cursos con
sus colores y «Esta semana en tu aula», está listo.

## 3. VPS con tu propio subdominio

Ejemplo con `stellar.moqueguasoft.com` sobre Ubuntu 22.04 o 24.04.
Todo lo que sigue está probado sobre Ubuntu 24.04 con Apache 2.4.58 y
PHP 8.3, salvo el paso de HTTPS, que necesita un dominio real.

**Usa Apache, no nginx.** No es gusto: `uploads/.htaccess` es lo que
impide que un archivo subido por un estudiante se ejecute como PHP, y
un `.htaccess` solo lo lee Apache con `AllowOverride All`. Con nginx
esa protección desaparece en silencio y hay que rehacerla a mano (al
final está cómo).

### 1. Apuntar el subdominio

En el DNS de `moqueguasoft.com`, un registro **A**:

```
stellar   A   <IP-de-tu-VPS>
```

Espera a que resuelva antes de pedir el certificado:

```bash
dig +short stellar.moqueguasoft.com
```

### 2. Paquetes

```bash
sudo apt update
sudo apt install -y apache2 libapache2-mod-php8.3 \
  php8.3-mysql php8.3-mbstring php8.3-curl php8.3-xml php8.3-gd \
  mariadb-server git certbot python3-certbot-apache
sudo mysql_secure_installation
```

### 3. El código

```bash
sudo git clone https://github.com/andreTYS/Stellar.git /var/www/stellar
cd /var/www/stellar
sudo rm -rf .git movil electron _site

sudo chown -R www-data:www-data /var/www/stellar
sudo find /var/www/stellar -type d -exec chmod 755 {} \;
sudo find /var/www/stellar -type f -exec chmod 644 {} \;
sudo chmod 775 /var/www/stellar/uploads
```

`movil/` y `electron/` no se usan en el servidor. `migrations/`,
`schema.sql` y `seed_data.sql` **sí** tienen que quedarse: son los que
usa el reinicio diario.

### 4. Base de datos y su usuario

**No uses root.** Apache corre como `www-data` y el root de MariaDB se
autentica por socket de Unix, así que la conexión falla con
`Access denied for user 'root'@'localhost'`. Hace falta un usuario con
contraseña:

```bash
CLAVE=$(openssl rand -hex 16)
echo "Guarda esta clave: $CLAVE"

sudo mysql -e "
CREATE DATABASE stellar_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'stellar'@'localhost' IDENTIFIED BY '$CLAVE';
GRANT ALL PRIVILEGES ON stellar_demo.* TO 'stellar'@'localhost';
FLUSH PRIVILEGES;"
```

Ese `GRANT` es solo sobre `stellar_demo`: si mañana pones otra base en
el mismo VPS, este usuario no la alcanza.

### 5. Configuración

```bash
cd /var/www/stellar
sudo cp includes/config.local.example.php includes/config.local.php
sudo nano includes/config.local.php
```

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stellar_demo');
define('DB_USER', 'stellar');
define('DB_PASS', 'la-clave-que-guardaste');

define('BASE_URL', '');      // el subdominio apunta a la raíz
define('MODO_DEMO', true);   // es una demostración pública
```

```bash
sudo chown www-data:www-data includes/config.local.php
sudo chmod 640 includes/config.local.php
```

`640` para que solo `www-data` pueda leerla: ahí dentro está la
contraseña de la base.

### 6. El sitio en Apache

```bash
sudo tee /etc/apache2/sites-available/stellar.conf > /dev/null <<'EOF'
<VirtualHost *:80>
    ServerName stellar.moqueguasoft.com
    DocumentRoot /var/www/stellar

    <Directory /var/www/stellar>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Nada de esto se sirve por web.
    <FilesMatch "^(config\.local\.php|.*\.sql|.*\.md)$">
        Require all denied
    </FilesMatch>
    <DirectoryMatch "/(migrations|herramientas|docs)/">
        Require all denied
    </DirectoryMatch>

    ErrorLog  ${APACHE_LOG_DIR}/stellar-error.log
    CustomLog ${APACHE_LOG_DIR}/stellar-access.log combined
</VirtualHost>
EOF

sudo a2enmod rewrite headers
sudo a2ensite stellar
sudo a2dissite 000-default
sudo apache2ctl configtest && sudo systemctl reload apache2
```

`AllowOverride All` es obligatorio: sin él, `uploads/.htaccess` se
ignora y un archivo subido podría ejecutarse.

### 7. Llenar la base

```bash
sudo -u www-data php /var/www/stellar/herramientas/reiniciar_demo.php
```

Debe decir «33 tablas», «24 usuarios» y terminar en menos de un
segundo.

### 8. HTTPS

```bash
sudo certbot --apache -d stellar.moqueguasoft.com
```

Certbot edita el vhost y deja la renovación automática puesta.
Compruébala con `sudo certbot renew --dry-run`.

### 9. El reinicio diario

```bash
sudo crontab -u www-data -e
```

```
0 4 * * *  /usr/bin/php /var/www/stellar/herramientas/reiniciar_demo.php >> /var/log/stellar-demo.log 2>&1
```

Como `www-data`, no como root: es el mismo usuario que escribe los
archivos subidos, así que no deja nada con dueño equivocado.

### 10. Comprobar

```bash
curl -I https://stellar.moqueguasoft.com/
curl -o /dev/null -s -w "%{http_code}\n" https://stellar.moqueguasoft.com/includes/config.local.php   # 403
curl -o /dev/null -s -w "%{http_code}\n" https://stellar.moqueguasoft.com/schema.sql                  # 403
```

Y entra con `EST-001` / `password`. Si ves los cursos con sus colores y
«Esta semana en tu aula», está listo.

### Si de todas formas usas nginx

Replica a mano lo que hace `uploads/.htaccess`, o cualquiera que
consiga subir un `.php` ahí lo ejecuta:

```nginx
location ^~ /uploads/ {
    location ~ \.php$ { deny all; }
    autoindex off;
}
location ~ ^/(includes/config\.local\.php|.*\.sql|migrations/|herramientas/|docs/) {
    deny all;
}
```


### Actualizar (sin Docker)

```bash
cd /var/www/stellar
sudo -u www-data git pull          # si conservaste .git
sudo -u www-data php herramientas/reiniciar_demo.php
```

El reinicio aplica las migraciones nuevas, porque recrea la base entera
desde `schema.sql` más `migrations/`. En un colegio de verdad **no** se
hace así: ahí las migraciones se aplican una a una y no se borra nada.

---

## 4. Con Docker (lo más simple si ya lo usas)

El repositorio trae `Dockerfile`, `docker-compose.yml` y `.env.ejemplo`.
Probado de punta a punta: imagen construida, pila levantada, los seis
roles entrando y el certificado en PDF descargándose.

```bash
git clone https://github.com/andreTYS/Stellar.git stellar
cd stellar

cp .env.ejemplo .env
nano .env          # cambia DB_ROOT_PASS y DB_PASS

docker compose up -d --build
```

Y ya está. En el primer arranque el contenedor espera a que MariaDB
responda, crea `config.local.php` a partir del entorno y llena la base.
A partir del segundo **no toca nada**: si ya hay usuarios, no se
reinstala. Eso es lo que evita que un `docker compose restart` borre el
trabajo de un colegio.

La plataforma queda en `http://<tu-vps>:8080`. Compruébalo:

```bash
docker compose ps                 # los dos en «healthy»
curl -I http://localhost:8080/
```

### Delante va tu proxy

El contenedor solo habla HTTP por el puerto 8080; el subdominio y el
certificado los pone lo que ya tengas corriendo.

**Traefik** — añade al servicio `web` en `docker-compose.yml`:

```yaml
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.stellar.rule=Host(`stellar.moqueguasoft.com`)"
      - "traefik.http.routers.stellar.entrypoints=websecure"
      - "traefik.http.routers.stellar.tls.certresolver=letsencrypt"
      - "traefik.http.services.stellar.loadbalancer.server.port=80"
    networks: [proxy, default]
```

**Nginx Proxy Manager** — nuevo Proxy Host: dominio
`stellar.moqueguasoft.com`, destino `http://<ip-del-host>:8080`, y
pídele el certificado desde la pestaña SSL.

**Nginx a pelo** en el VPS:

```nginx
server {
    server_name stellar.moqueguasoft.com;
    client_max_body_size 12M;          # las fotos de los entregables
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

`proxy_set_header Host $host` no es opcional: el pie del certificado en
PDF y los enlaces de verificación se arman con el dominio por el que
entró la petición. Sin esa línea saldría `127.0.0.1:8080`.

Después, `sudo certbot --nginx -d stellar.moqueguasoft.com`.

### El reinicio diario

En el cron del **host**, no dentro del contenedor:

```
0 4 * * *  cd /ruta/a/stellar && docker compose exec -T web php herramientas/reiniciar_demo.php >> /var/log/stellar-demo.log 2>&1
```

### Lo que hay que saber de la imagen

- **Apache y no nginx dentro del contenedor**, a propósito:
  `uploads/.htaccess` es lo que impide que un archivo subido se ejecute
  como PHP, y solo Apache lo lee. Comprobado dentro del contenedor:
  un `.php` dejado en `uploads/` se sirve como texto plano.
- **`composer install` va en una etapa aparte**, así que la imagen final
  no arrastra composer ni su caché. Esto hace que el **certificado en
  PDF funcione**: necesita dompdf, y sin `vendor/` la descarga daba un
  500 con «Falta instalar las dependencias».
- **Sin `gd`.** El certificado es HTML y CSS sin una sola imagen, y
  dompdf lo renderiza sin gd. La imagen base ya trae curl, dom,
  fileinfo, mbstring y libxml; solo se añade `pdo_mysql`.
- **La base no publica puertos.** Solo se ve desde la red interna de
  compose. Si le pusieras `ports:`, quedaría expuesta a internet.
- **Dos volúmenes con nombre**: `datos_db` y `subidas`. Reconstruir la
  imagen no borra ni la base ni las fotos que subieron los estudiantes.

### Actualizar

```bash
cd /ruta/a/stellar
git pull
docker compose up -d --build
```

La base no se toca: el arranque ve que ya hay usuarios y no reinstala.
Si una versión nueva trae migraciones, aplícalas tú:

```bash
docker compose exec -T db sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' < migrations/0XX_lo_que_sea.sql
```

En el demo es más fácil: `reiniciar_demo.php` recrea la base entera con
las migraciones nuevas incluidas.

---

## Lo que hay que saber del modo demostración

Con `MODO_DEMO` activado se bloquean dos cosas, y solo dos:

- Cambiar la contraseña desde el perfil.
- Desactivar cuentas desde la administración.

No es por seguridad, es por que el demo siga en pie: son cuentas
compartidas, y el primero que cambiara una contraseña dejaría fuera a
todos los demás hasta la madrugada. Todo lo demás —crear aulas,
calificar, pasar asistencia, importar usuarios— se puede tocar, porque
es justo lo que quieres que toquen.

## Lo que NO debe salir a internet así

- **Un colegio de verdad no va en el mismo servidor que el demo.** Las
  cuentas de prueba tienen la contraseña escrita en la pantalla de
  entrada.
- **`MODO_DEMO` fuera** en una instalación real, o el colegio no podrá
  cambiar sus propias contraseñas.
- **`reiniciar_demo.php` fuera** de una instalación real. Borra la base.
  Bórralo del servidor o quítale el token.
- **`CHATBOT_CLAVE_MAESTRA`** solo en `config.local.php`, que está
  fuera del repositorio. Si falta, la plataforma se niega a guardar
  claves de API en vez de guardarlas en claro.
