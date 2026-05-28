# === Stage 1: composer dependencies ===
# Install prod-only deps in isolated builder stage so final image
# doesn't carry composer binary or build-time dev packages.
FROM composer:2.9 AS composer-deps

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist

# === Stage 2: application runtime ===
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        gettext-base \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql bcmath gd zip intl exif opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Bring composer in for the dump-autoload step below (vendor was already installed in builder stage).
COPY --from=composer:2.9 /usr/bin/composer /usr/local/bin/composer

# Production PHP config (OPcache enabled, expose_php=0, sane upload limits)
COPY docker/railway/php.ini /usr/local/etc/php/conf.d/zz-production.ini

# nginx + supervisord + entrypoint
COPY docker/railway/nginx.conf.template /etc/nginx/sites-available/default.template
RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/railway/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/railway/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# Bring in vendor from builder, then copy app source.
COPY --from=composer-deps /app/vendor ./vendor
COPY . .

# Generate optimized autoload now that full app code is present,
# fix permissions for Laravel writable dirs,
# pre-create nginx runtime dirs (else nginx refuses to start under www-data).
RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && mkdir -p /var/run/nginx /var/lib/nginx /var/log/nginx \
    && chown -R www-data:www-data /var/run/nginx /var/lib/nginx /var/log/nginx

# Railway assigns PORT dynamically; nginx binds to it via entrypoint envsubst.
# No EXPOSE — Railway ignores it and routes based on $PORT.

CMD ["/usr/local/bin/entrypoint.sh"]
