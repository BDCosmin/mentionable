ARG APP_ENV=prod

FROM php:8.3-apache

# Enable Apache rewrite module early
RUN a2enmod rewrite

# Install required libs and PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install intl mbstring zip pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy composer binary from official composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Create var folder if missing
RUN mkdir -p var

# Make sure www-data owns the project files
RUN chown -R www-data:www-data /var/www/html

# Switch to www-data user for running composer (to avoid permission issues)
USER www-data

# Run composer install depending on environment
RUN if [ "$APP_ENV" = "prod" ]; then \
      COMPOSER_CACHE_DIR=/tmp composer install --no-dev --optimize-autoloader; \
    else \
      composer install; \
    fi

# Switch back to root for Apache
USER root

# Copy Apache config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]