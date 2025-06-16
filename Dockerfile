ARG APP_ENV=prod

FROM php:8.3-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install required system libs and PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install intl mbstring zip pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Copy composer binary from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Install dependencies depending on environment
RUN if [ "$APP_ENV" = "prod" ]; then \
      COMPOSER_CACHE_DIR=/tmp composer install --no-dev --optimize-autoloader; \
    else \
      composer install; \
    fi

# Debug: List vendor directory contents to verify
RUN ls -la /var/www/html/vendor

# Copy the rest of the app
COPY . .

# Fix ownership and permissions
RUN chown -R www-data:www-data /var/www/html

# Copy Apache config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Run Apache in the foreground
CMD ["apache2-foreground"]