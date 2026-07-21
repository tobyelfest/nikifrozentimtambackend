FROM php:8.2-apache

# Install dependency yang dibutuhin Laravel
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extension PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# ========= PERBAIKAN MPM (Paling Penting) =========
# Matiin semua MPM, nyalain cuma prefork (biar PHP jalan)
RUN a2dismod mpm_event mpm_worker || true && \
    a2dismod mpm_prefork || true && \
    a2enmod mpm_prefork && \
    a2enmod rewrite

# Copy konfigurasi virtual host
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Set permission folder storage & cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# ========= PERBAIKAN PORT RAILWAY =========
# Di runtime, Apache otomatis pake port yang dikasih Railway ($PORT)
CMD sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf && apache2-foreground
