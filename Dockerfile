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


# ================================
# FIX APACHE MPM CONFLICT
# ================================

RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork \
    && a2enmod rewrite


# Debug MPM
RUN ls -la /etc/apache2/mods-enabled | grep mpm || true


RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data \
    storage \
    bootstrap/cache


EXPOSE 80

CMD ["apache2-foreground"]
