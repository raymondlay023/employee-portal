# Multi-stage Dockerfile for Laravel 12 production

FROM node:18 AS node_builder
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --silent
COPY . .
RUN npm run build

FROM composer:2 AS composer_builder
WORKDIR /app
COPY composer.json composer.lock* ./
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.2-fpm
WORKDIR /var/www/html

# Install system deps + extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
  && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd xml zip \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && rm -rf /var/lib/apt/lists/*

# Secure PHP upload limits for production
RUN echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/uploads.ini

# Copy Composer binary
COPY --from=composer_builder /usr/bin/composer /usr/bin/composer

# Copy app
COPY --from=composer_builder /app /var/www/html
COPY --from=node_builder /app/public/build /var/www/html/public/build

# Create Laravel directories
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/app/private/biometric

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Production config
ENV APP_ENV=production
ENV APP_DEBUG=false

# Optimize Laravel autoloading
RUN composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi

# Setup entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm"]