FROM php:8.3-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# 3) Install Composer (PHP package manager)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4) Set the working directory inside the container
WORKDIR /var/www/html

# 5) Copy only composer files first (this helps Docker cache)
COPY . .

# 6) Install PHP dependencies
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-interaction


# 7) Now copy the rest of the app code
#COPY . .

# 8) Expose port 8000 (we'll map 80 -> 8000 later)
EXPOSE 8000

# 9) Start Laravel built-in server when container runs
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]