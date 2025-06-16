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
    && docker-php-ext-install intl mbstring zip pdo pdo_mysql

# Copy composer binary from official composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Create var folder if missing
RUN mkdir -p var

# Set ownership (optional, maybe adjust depending on env)
RUN chown -R www-data:www-data /var/www/html

# Run composer differently depending on environment
RUN if [ "$APP_ENV" = "prod" ]; then \
      COMPOSER_CACHE_DIR=/tmp composer install --no-dev --optimize-autoloader; \
    else \
      composer install; \
    fi

# Copy Apache config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 80

CMD ["apache2-foreground"]