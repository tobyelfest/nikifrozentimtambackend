FROM php:8.3-apache

WORKDIR /var/www/html


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


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader


COPY . .


RUN composer dump-autoload --optimize --no-dev


COPY docker/000-default.conf \
    /etc/apache2/sites-available/000-default.conf


# ===============================
# FIX APACHE MPM CONFLICT
# ===============================

RUN a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2dismod mpm_prefork || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite


RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache


EXPOSE 80


CMD ["apache2-foreground"]
