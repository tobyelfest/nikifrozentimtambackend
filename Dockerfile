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
# No scripts because .env does not exist during build
# ============================================================

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader


# ============================================================
# Copy Laravel Application
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


# Enable Apache Rewrite
RUN a2enmod rewrite


# ============================================================
# Apache MPM Prefork
# Required for mod_php
# ============================================================

RUN rm -f /etc/apache2/mods-enabled/mpm_* \
    && a2enmod mpm_prefork


# ============================================================
# Laravel Permission
# ============================================================

RUN chown -R www-data:www-data \
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
