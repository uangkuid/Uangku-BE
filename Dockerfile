# Stage 1: Install PHP dependencies
FROM composer:2 AS vendor

WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install required PHP extensions
RUN install-php-extensions intl

# Install dependencies without scripts and dev dependencies for production
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist

# Stage 2: FrankenPHP production image
FROM dunglas/frankenphp AS production

# Install required PHP extensions
RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    intl \
    opcache \
    zip \
    curl

WORKDIR /app

# Copy vendor dependencies from previous stage
COPY --from=vendor /app/vendor ./vendor

# Copy application files (excluding files listed in .dockerignore)
COPY . .

# Copy environment file
COPY .env .env

# Create non-root user for security
RUN addgroup --system --gid 1001 laravel \
    && adduser --system --uid 1001 --gid 1001 laravel

# Set proper permissions
RUN chown -R laravel:laravel /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache

# Switch to non-root user
USER laravel

# Create storage link and optimize Laravel
RUN php artisan storage:link \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Switch back to root for final setup
USER root

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1

# Switch to non-root user for runtime
USER laravel

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
