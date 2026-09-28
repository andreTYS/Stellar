# ============================================================
# INNOVA-STEAM — Imagen de la plataforma
#
# Se parte de php:8.3-apache y NO de una imagen con nginx a propósito:
# uploads/.htaccess es lo que impide que un archivo subido por un
# estudiante se ejecute como PHP, y un .htaccess solo lo lee Apache con
# AllowOverride All. Con nginx esa protección desaparece en silencio.
# ============================================================

# ── Dependencias de composer, en su propia etapa ─────────────
# Así el vendor/ se construye una vez y no arrastra composer ni su
# caché a la imagen final.
FROM composer:2 AS dependencias
WORKDIR /app
COPY composer.json composer.lock ./
# --no-dev: dompdf, guzzle y el SDK, sin las herramientas de desarrollo.
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist \
    && composer clear-cache

# ── Imagen final ─────────────────────────────────────────────
FROM php:8.3-apache

# Solo pdo_mysql. La imagen base ya trae curl, dom, fileinfo, mbstring,
# libxml y openssl, que es todo lo que usan la plataforma, dompdf y
# guzzle. No hace falta gd: el certificado en PDF es HTML y CSS, sin
# una sola imagen, y dompdf lo renderiza sin gd.
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql

RUN a2enmod rewrite headers

# Las fotos de los entregables llegan a 5 MB y el CSV de importación a
# 2 MB; los valores por defecto de PHP se quedan cortos.
RUN { \
      echo 'upload_max_filesize = 8M'; \
      echo 'post_max_size = 12M'; \
      echo 'memory_limit = 256M'; \
      echo 'display_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'error_log = /dev/stderr'; \
      echo 'date.timezone = America/Lima'; \
    } > /usr/local/etc/php/conf.d/innovasteam.ini

# AllowOverride All: sin esto uploads/.htaccess se ignora y un archivo
# subido podría ejecutarse. Los bloqueos son los mismos que en la guía
# del VPS sin Docker.
RUN { \
      echo '<Directory /var/www/html>'; \
      echo '    Options -Indexes +FollowSymLinks'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '</Directory>'; \
      echo '<FilesMatch "^(config\.local\.php|.*\.sql|.*\.md)$">'; \
      echo '    Require all denied'; \
      echo '</FilesMatch>'; \
      echo '<DirectoryMatch "/(migrations|herramientas|docs)/">'; \
      echo '    Require all denied'; \
      echo '</DirectoryMatch>'; \
      echo 'ServerTokens Prod'; \
      echo 'ServerSignature Off'; \
    } > /etc/apache2/conf-available/innovasteam.conf \
    && a2enconf innovasteam

WORKDIR /var/www/html

# movil/, electron/ y el resto que no se usa aquí quedan fuera por
# .dockerignore.
COPY --chown=www-data:www-data . /var/www/html/
COPY --from=dependencias --chown=www-data:www-data /app/vendor /var/www/html/vendor

RUN mkdir -p /var/www/html/uploads/entregables \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod 775 /var/www/html/uploads /var/www/html/uploads/entregables

# Espera a que la base responda antes de arrancar Apache: al levantar
# todo junto, MariaDB tarda unos segundos y sin esto la primera visita
# se encuentra la plataforma sin base.
COPY docker/arranque.sh /usr/local/bin/arranque.sh
RUN chmod +x /usr/local/bin/arranque.sh

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1/login.php") ? 0 : 1);'

EXPOSE 80
CMD ["/usr/local/bin/arranque.sh"]
