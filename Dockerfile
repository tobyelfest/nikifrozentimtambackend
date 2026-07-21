FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
&& apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite
# FIX MPM CONFLICT: Disable all, enable only prefork (required for PHP)
RUN a2dismod mpm_event mpm_worker || true
RUN a2dismod mpm_prefork || true
RUN a2enmod mpm_prefork
RUN a2enmod rewrite

# Copy virtual host config
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Fix Railway Port (Change Apache Listen port to Railway's PORT env)
RUN sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
EXPOSE ${PORT}

# Start Apache
CMD ["apache2-foreground"]
