# ---------------------------------------------------------------------------
# Mango Tree School Management System - production image
#
# Deployed to Render as a Docker service (https://render.com/docs/docker):
# Render builds this file, then runs `docker/start.sh`, which makes Apache
# listen on the port Render provides in $PORT.
#
# The application is Laravel 5.5, which requires PHP 7.2, hence the legacy
# php:7.2-apache base image.
# ---------------------------------------------------------------------------
FROM php:7.2-apache

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# 1. Debian Buster is end-of-life, so apt must use the archive mirrors.
# 2. Install the PHP extensions Laravel plus the PDF/Excel/upload features need.
# 3. Enable the Apache modules the application's public/.htaccess relies on.
RUN set -eux; \
    sed -i \
        -e 's!deb.debian.org/debian!archive.debian.org/debian!g' \
        -e 's!security.debian.org/debian-security!archive.debian.org/debian-security!g' \
        /etc/apt/sources.list; \
    sed -i '/buster-updates/d' /etc/apt/sources.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        zlib1g-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        libssl-dev \
        libzip-dev; \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring tokenizer xml zip bcmath exif gd opcache; \
    a2enmod rewrite headers; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# Apache serves the Laravel public/ directory instead of the project root, and
# public/.htaccess (Laravel's pretty URLs, Authorization header) must be read,
# which requires AllowOverride All for that directory.
# ---------------------------------------------------------------------------
RUN set -eux; \
    sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf; \
    sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf; \
    printf '<Directory /var/www/html/public>\n\tOptions FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/laravel.conf; \
    printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf; \
    a2enconf laravel servername

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# PHP dependencies are installed at build time so container starts are fast.
# composer.lock must be committed to keep builds reproducible. The wildcard
# keeps the COPY valid even when composer.lock is missing.
#
# composer.json puts database/seeds + database/factories in its classmap and
# app/Http/Helpers/helpers.php in its "files" autoload, and the Composer plugin
# kylekatarnls/update-helper loads that autoloader while Composer runs, so both
# paths must exist before `composer install` (--no-plugins keeps the build
# independent of plugin behaviour).
# ---------------------------------------------------------------------------
COPY composer.* ./
COPY database/ ./database/
COPY app/Http/Helpers/ ./app/Http/Helpers/

RUN set -eux; \
    if [ -f composer.lock ]; then \
        composer install --no-dev --no-interaction --no-scripts --no-plugins --prefer-dist --optimize-autoloader; \
    else \
        composer update --no-dev --no-interaction --no-scripts --no-plugins --prefer-dist --optimize-autoloader; \
    fi

# Laravel 5.5 cannot read the Composer 2 installed.json format.
# docker/start.sh runs the same script again, so a mounted vendor/ is fixed too.
COPY docker/patch-package-manifest.php /usr/local/bin/patch-package-manifest.php
RUN php /usr/local/bin/patch-package-manifest.php /var/www/html

# ---------------------------------------------------------------------------
# Application code, PHP settings and the entrypoint.
# ---------------------------------------------------------------------------
COPY . .
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

RUN set -eux; \
    chmod +x docker/start.sh; \
    ln -sf /var/www/html/docker/start.sh /usr/local/bin/start.sh; \
    mkdir -p storage/app/public storage/framework/cache storage/framework/sessions \
             storage/framework/views storage/framework/testing storage/logs bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache

# Render forwards traffic to the port named in $PORT (10000 by default).
ENV PORT=10000
EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=5s --start-period=120s --retries=5 \
    CMD curl -fsS "http://127.0.0.1:${PORT}/healthz" || exit 1

CMD ["/usr/local/bin/start.sh"]