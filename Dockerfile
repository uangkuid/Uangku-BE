FROM dunglas/frankenphp

# Install ekstensi PHP yang dibutuhkan Laravel
RUN install-php-extensions pcntl pdo_mysql

WORKDIR /app

# Salin semua source code Laravel kamu
COPY . /app

# 🧙‍♂️ Salin folder vendor dari image yang kamu simpan di registry
COPY --from=oratakashi/laravel-12-vendor /app/vendor /app/vendor

# Jalankan package discover jika dibutuhkan
RUN php artisan package:discover --ansi || true

# Jalankan Laravel via FrankenPHP
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
