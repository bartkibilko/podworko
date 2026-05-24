FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql bcmath gd zip intl exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.9 /usr/bin/composer /usr/local/bin/composer

RUN if ! getent group ${GID} >/dev/null; then groupadd -g ${GID} dev; fi \
    && useradd -u ${UID} -g ${GID} -ms /bin/bash dev

WORKDIR /var/www/html
USER dev
