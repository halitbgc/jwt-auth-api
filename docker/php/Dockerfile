# Start with the official PHP image
FROM php:8.1-fpm

# Needed for installing PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Timezone setting
ENV TZ=Europe/Istanbul

# Install additional PHP extensions
RUN echo "date.timezone=Europe/Istanbul" > /usr/local/etc/php/conf.d/timezone.ini

# Install the PHP extensions we need
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Working directory
WORKDIR /var/www/html





