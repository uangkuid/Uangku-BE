# Stage 1: Install dependencies
FROM php:8.3-alpine AS vendor

WORKDIR /app

# Install dependencies and composer with signature verification
RUN apk add --no-cache \
    icu-dev \
    curl \
    git \
    unzip \
    && docker-php-ext-install intl \
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

# Install ICU runtime and build deps temporarily for compiling intl extension
RUN apk add --no-cache icu-libs \
    && apk add --no-cache --virtual .build-deps icu-dev \
    && docker-php-ext-install intl pcntl pdo_mysql \
    && apk del .build-deps

COPY --from=vendor /app /app

COPY .env /app/.env

EXPOSE 8000

WORKDIR /app

RUN php artisan storage:link

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
