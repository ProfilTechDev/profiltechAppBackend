# syntax=docker/dockerfile:1.7

# ==============================================================================
# Stage 1: Composer vendor build
# ==============================================================================
FROM composer:2 AS vendor

WORKDIR /app

# Copy only files needed to resolve dependencies first (layer cache)
COPY composer.json composer.lock ./

# Install production dependencies without running scripts (artisan not available yet)
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist

# Copy the rest of the application source
COPY . .

# Generate the optimized, class-mapped autoloader with full source present
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ==============================================================================
# Stage 2: Runtime image (FrankenPHP — Caddy + PHP in one process)
# ==============================================================================
FROM dunglas/frankenphp:1-php8.4 AS runtime

# Install runtime utilities we need (curl is used by the healthcheck)
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions Laravel commonly needs in production
RUN install-php-extensions \
        pdo_mysql \
        bcmath \
        intl \
        gd \
        zip \
        exif \
        pcntl \
        opcache \
        redis

# Use production php.ini as base
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# OPcache tuned for production
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

# FrankenPHP / Caddy site config for Laravel
COPY docker/Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

# Copy built application from the vendor stage
COPY --from=vendor /app /app

# Entrypoint script handles migrate + optimize before starting FrankenPHP
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Ensure Laravel can write to storage and bootstrap/cache
RUN chmod -R ug+w /app/storage /app/bootstrap/cache

EXPOSE 80

# Laravel 11+ exposes /up for health probes
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
