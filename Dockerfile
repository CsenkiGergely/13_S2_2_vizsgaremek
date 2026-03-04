FROM php:8.2-cli

# Szükséges PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath gd

# Composer telepítése
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Munkamappa
WORKDIR /var/www

# Projekt fájlok másolása ← EZT JAVÍTOTTUK!
COPY campsite/backend/ .

# Composer csomagok telepítése
RUN composer install --no-dev --optimize-autoloader

# .env beállítás
RUN cp .env.example .env && php artisan key:generate

# Port megnyitása
EXPOSE 10000

# Laravel indítása
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
