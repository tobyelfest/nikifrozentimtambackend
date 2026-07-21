FROM php:8.3-apache

WORKDIR /var/www/html

# Install PHP extensions yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy project Laravel
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Install dependency Laravel
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Konfigurasi Apache Laravel
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Aktifkan Apache rewrite untuk Laravel routing
RUN a2enmod rewrite

# Permission Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Laravel cache optimization (akan dijalankan saat env sudah tersedia)
# RUN php artisan config:cache
# RUN php artisan route:cache
# RUN php artisan view:cache

EXPOSE 80

CMD ["apache2-foreground"]
