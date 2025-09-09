# Stage 1: Install dependencies
FROM composer:2 AS vendor

WORKDIR /app
COPY . /app

RUN install-php-extensions intl

RUN composer install --no-dev --optimize-autoloader

# Stage 2: FrankenPHP with app code and vendor
FROM dunglas/frankenphp

RUN install-php-extensions pcntl pdo_mysql intl

COPY --from=vendor /app /app

COPY .env /app/.env

EXPOSE 8000

WORKDIR /app

RUN php artisan storage:link

RUN php artisan icons:cache
RUN php artisan filament:optimize
RUN php artisan optimize
RUN php artisan config:clear

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
