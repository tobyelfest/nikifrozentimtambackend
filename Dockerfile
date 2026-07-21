FROM php:8.3-apache

WORKDIR /var/www/html

# 1. Install dependensi sistem dan PHP Extensions
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Copy Composer binary langsung dari official image (lebih praktis)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Copy file composer lebih dulu untuk memanfaatkan Docker Layer Cache
COPY composer.json composer.lock ./

# 4. Install dependency vendor (gunakan --no-scripts agar tidak crash saat build)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# 5. Copy seluruh sisa source code project Laravel
COPY . .

# 6. Generate autoloader setelah seluruh kode project ter-copy
RUN composer dump-autoload --optimize --no-dev

# 7. Konfigurasi Apache Laravel
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# 8. Aktifkan Apache rewrite untuk routing Laravel
RUN a2enmod rewrite

# 9. Set permission folder storage & cache
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
