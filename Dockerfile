FROM dunglas/frankenphp

RUN install-php-extensions pcntl

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

COPY . /app

RUN composer install --no-dev --optimize-autoloader

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
