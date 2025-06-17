ARG APP_ENV=prod

FROM php:8.3-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install system libs and PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install intl mbstring zip pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Copy composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files first (INCLUDING composer.json, .lock, and rest of app)
COPY . .

# Install dependencies AFTER all files are present
RUN if [ "$APP_ENV" = "prod" ]; then \
      COMPOSER_CACHE_DIR=/tmp composer install --no-dev --optimize-autoloader; \
    else \
      composer install; \
    fi

# Fix ownership and permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Copy Apache config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 80

# Launch Apache
CMD ["apache2-foreground"]