FROM php:8.3-apache

WORKDIR /var/www/html


# ============================================================
# Install dependencies + PHP extensions
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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ============================================================
# Install Laravel dependencies (cache friendly)
# ============================================================

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader


# ============================================================
# Copy Laravel source
# ============================================================

COPY . .


# ============================================================
# Generate autoload
# ============================================================

RUN composer dump-autoload \
    --optimize \
    --no-dev


# ============================================================
# Apache Laravel Config
# ============================================================

COPY docker/000-default.conf \
    /etc/apache2/sites-available/000-default.conf


# Enable Laravel rewrite
RUN a2enmod rewrite


# ============================================================
# Laravel Permission
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
# Railway
# ============================================================

EXPOSE 80


CMD ["apache2-foreground"]
