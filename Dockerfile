FROM php:8.2-apache

# Szükséges PHP extensions + Node.js
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql mbstring zip exif pcntl bcmath gd

# Node.js telepítése (QR dekódoláshoz kell)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Composer telepítése
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Munkamappa
WORKDIR /var/www/html

# Projekt fájlok másolása
COPY campsite/backend/ .

# Composer csomagok telepítése
RUN composer install --no-dev --optimize-autoloader

# Node.js QR dekódoló csomagok telepítése
RUN cd scripts && npm install && cd ..

# .env beállítás
RUN cp .env.example .env && php artisan key:generate

# Apache document root beállítása Laravel public mappára
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Apache mod_rewrite engedélyezése
RUN a2enmod rewrite

# Jogosultságok beállítása
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Port
EXPOSE 10000

# Migráció + Apache indítása
CMD php artisan migrate --force && apache2-foreground
