# Stage 1: Install dependencies
FROM composer:2 AS vendor

COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader

# Stage 2: FrankenPHP with app code and vendor
FROM dunglas/frankenphp

RUN install-php-extensions pcntl

COPY --from=vendor /app/vendor /app/vendor
COPY . /app

WORKDIR /app
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
