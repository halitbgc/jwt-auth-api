# PHP image ile başla
FROM php:8.1-fpm

# Gerekli kütüphaneleri yükle
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Composer kur
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma dizini
WORKDIR /var/www/html
