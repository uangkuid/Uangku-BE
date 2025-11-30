# Stage 1: Install dependencies
FROM composer/composer:latest AS vendor

WORKDIR /app

RUN apk add --no-cache \
    php83-intl \
    php83-pcntl \
    php83-pdo_mysql \
    && ln -s /usr/bin/php83 /usr/bin/php || true

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy remaining source code
COPY . /app

# Stage 2: FrankenPHP with app code and vendor
FROM dunglas/frankenphp:latest-php8.3-alpine

RUN apk add --no-cache \
    php83-intl \
    php83-pcntl \
    php83-pdo_mysql \
    && ln -s /usr/bin/php83 /usr/bin/php || true

COPY --from=vendor /app /app

COPY .env /app/.env

EXPOSE 8000

WORKDIR /app

RUN php artisan storage:link

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
