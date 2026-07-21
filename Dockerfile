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

# ========= PERBAIKAN MPM (YANG BENER) =========
RUN rm -f /etc/apache2/mods-available/mpm_event.* \
         /etc/apache2/mods-available/mpm_worker.* \
         /etc/apache2/mods-available/mpm_prefork.* \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* \
         /etc/apache2/mods-enabled/mpm_worker.* \
         /etc/apache2/mods-enabled/mpm_prefork.* \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Copy virtual host
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Bikin folder yang diperlukan
RUN mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/storage

# Set permission
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf && apache2-foreground
