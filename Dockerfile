# Stage 1: Install dependencies
FROM php:8.3-alpine AS vendor

WORKDIR /app

# Install PHP extensions using install-php-extensions (precompiled when available)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.7.2/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl

# Install composer
RUN apk add --no-cache curl git unzip \
    && EXPECTED_HASH="$(curl -sSf https://composer.github.io/installer.sig)" \
    && curl -sSf https://getcomposer.org/installer -o composer-setup.php \
    && ACTUAL_HASH="$(php -r "echo hash_file('sha384', 'composer-setup.php');")" \
    && if [ "$EXPECTED_HASH" != "$ACTUAL_HASH" ]; then echo 'ERROR: Invalid installer signature'; rm composer-setup.php; exit 1; fi \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy remaining source code
COPY . /app

# Stage 2: FrankenPHP with app code and vendor
FROM dunglas/frankenphp:latest-php8.3-alpine

# Install PHP extensions using install-php-extensions (precompiled when available)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.7.2/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl pcntl pdo_mysql

COPY --from=vendor /app /app

COPY .env /app/.env

EXPOSE 8000

WORKDIR /app

RUN php artisan storage:link

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
