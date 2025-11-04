FROM php:8.3-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev \
    && docker-php-ext-install pdo_mysql intl gd zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
