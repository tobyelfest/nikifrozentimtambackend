FROM php:8.3-apache

WORKDIR /var/www/html


# ============================================================
# Install System Dependencies + PHP Extensions
# Laravel 10 Requirements
# ============================================================

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# ============================================================
# Install Composer
# ============================================================

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


# ============================================================
# Copy Composer Files First
# Docker Cache Optimization
# ============================================================

COPY composer.json composer.lock ./


# ============================================================
# Install Laravel Dependencies
# ============================================================

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader


# ============================================================
# Copy Laravel Source Code
# ============================================================

COPY . .


# ============================================================
# Generate Optimized Autoload
# ============================================================

RUN composer dump-autoload \
    --optimize \
    --no-dev


# ============================================================
# Apache Laravel Configuration
# ============================================================

COPY docker/000-default.conf \
    /etc/apache2/sites-available/000-default.conf


# Enable Laravel Rewrite
RUN a2enmod rewrite


# ============================================================
# Laravel Folder Permission
# ============================================================

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache


# ============================================================
# Railway Port
# ============================================================

EXPOSE 80


# ============================================================
# Start Apache
# ============================================================

CMD ["apache2-foreground"]
