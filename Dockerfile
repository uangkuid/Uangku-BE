FROM dunglas/frankenphp

RUN install-php-extensions pcntl

COPY . /app

RUN composer install --no-dev --optimize-autoloader

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
