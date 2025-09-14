# Stage 1: Composer dependencies
FROM composer:2 as composer_stage
WORKDIR /var/www

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Stage 2: Node.js for Vue.js build
FROM node:18-alpine as node_stage
WORKDIR /var/www

# Copy package files
COPY package.json package-lock.json ./

# Install Node dependencies
RUN npm ci

# Copy source files
COPY . .

# Build Vue.js assets
RUN npm run build

# Stage 3: PHP-FPM with built assets
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy vendor from composer stage
COPY --from=composer_stage /var/www/vendor ./vendor

# Copy built assets from node stage
COPY --from=node_stage /var/www/public/build ./public/build

# Copy Laravel application
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
