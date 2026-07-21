FROM php:8.2-apache

# Install dependency + libzip-dev
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (termasuk zip)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# ========= PERBAIKAN MPM (PASTI BERHASIL) =========
# Hapus SEMUA konfigurasi MPM (file fisik, bukan symlink)
RUN rm -f /etc/apache2/mods-available/mpm_*.load \
         /etc/apache2/mods-available/mpm_*.conf \
         /etc/apache2/mods-enabled/mpm_*.load \
         /etc/apache2/mods-enabled/mpm_*.conf

# Buat ulang file prefork dari nol
RUN echo "LoadModule mpm_prefork_module /usr/lib/apache2/modules/mod_mpm_prefork.so" > /etc/apache2/mods-available/mpm_prefork.load && \
    echo "# mpm_prefork configuration" > /etc/apache2/mods-available/mpm_prefork.conf && \
    echo "StartServers 2" >> /etc/apache2/mods-available/mpm_prefork.conf && \
    echo "MinSpareServers 1" >> /etc/apache2/mods-available/mpm_prefork.conf && \
    echo "MaxSpareServers 3" >> /etc/apache2/mods-available/mpm_prefork.conf && \
    echo "MaxRequestWorkers 10" >> /etc/apache2/mods-available/mpm_prefork.conf

# Aktifkan prefork dan rewrite
RUN a2enmod mpm_prefork && a2enmod rewrite

# Copy virtual host
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN mkdir -p /var/www/html/bootstrap/cache /var/www/html/storage
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# ========= START COMMAND DEFAULT (JANGAN DIUBAH) =========
CMD sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf && apache2-foreground
