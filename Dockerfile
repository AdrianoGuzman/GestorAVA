# Imagen de demo temporal (Railway/Render), NO para producción real:
# corre con `php artisan serve` en un solo proceso, sin nginx/php-fpm
# ni workers de cola/schedule. Suficiente para que un cliente navegue
# la app; no pensado para carga real ni para quedar permanente.

FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl libpq-dev libzip-dev libicu-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/*

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql mbstring gd zip bcmath intl pcntl exif

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/app
COPY . .

# Con --dev (no --no-dev): los seeders de la demo usan factories con
# fake() (fakerphp/faker, require-dev) para cargar los usuarios de prueba.
RUN composer install --optimize-autoloader --no-interaction
RUN npm ci && npm run build

RUN chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
