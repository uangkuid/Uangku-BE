# Stage 1: Install dependencies
FROM ghcr.io/uangkuid/infra-php-base-frankenphp:latest AS vendor

WORKDIR /app
COPY . /app

RUN composer install --optimize-autoloader

# Stage 2: FrankenPHP with app code and vendor
FROM ghcr.io/uangkuid/infra-php-base-frankenphp:latest

COPY --from=vendor /app /app

COPY .env /app/.env

EXPOSE 8000

WORKDIR /app

RUN php artisan storage:link

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
