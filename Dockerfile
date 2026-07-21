FROM php:8.2-apache

# Install semua dependency + libzip-dev (penting buat excel)
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

# Install PHP extensions (tambahin zip)
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# ========= PERBAIKAN MPM APACHE =========
RUN a2dismod mpm_event mpm_worker || true && \
    a2dismod mpm_prefork || true && \
    a2enmod mpm_prefork && \
    a2enmod rewrite

# Copy konfigurasi virtual host
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Biarkan Composer jalan sebagai root (di container aman)
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies Laravel (tanpa dev)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Set permission folder storage & bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port (Railway nanti inject port)
EXPOSE 80

# Start Apache dengan port dari Railway
CMD sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf && apache2-foreground
