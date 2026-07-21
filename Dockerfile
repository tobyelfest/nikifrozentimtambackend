FROM php:8.3-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_mysql zip

COPY . .

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data storage bootstrap/cache

RUN a2enmod rewrite

# Laravel public folder
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
