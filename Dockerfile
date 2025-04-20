# Stage 1: Install dependencies
FROM composer:2 AS vendor

WORKDIR /app
COPY . /app
RUN composer install --no-dev --optimize-autoloader

# Stage 2: FrankenPHP with app code and vendor
FROM dunglas/frankenphp

RUN install-php-extensions pcntl pdo_mysql

COPY --from=vendor /app /app

EXPOSE 8000

WORKDIR /app
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
