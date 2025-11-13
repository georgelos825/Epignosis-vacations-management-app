FROM php:8.4-apache

# system deps for pgsql
# Install required system packages (git, pdo_pgsql, etc)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy app source
WORKDIR /var/www/html
COPY . .

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose API port
EXPOSE 8000

# Run PHP dev server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
