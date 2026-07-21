FROM php:8.2-apache

# Install dependency + libzip-dev
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (termasuk zip)
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# ========= PERBAIKAN MPM (PASTI BERHASIL) =========
# Hapus semua file .load dan .conf untuk MPM
RUN rm -f /etc/apache2/mods-available/mpm_*.load \
         /etc/apache2/mods-available/mpm_*.conf \
         /etc/apache2/mods-enabled/mpm_*.load \
         /etc/apache2/mods-enabled/mpm_*.conf

# Aktifkan hanya mpm_prefork (buat symlink manual)
RUN ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && a2enmod rewrite

# Copy virtual host
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copy seluruh project
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Buat folder yang diperlukan (jika belum ada)
RUN mkdir -p /var/www/html/bootstrap/cache /var/www/html/storage

# Set permission
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Start Apache dengan port dari Railway
CMD sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf && apache2-foreground
