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
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');" \
    && rm -rf /var/lib/apt/lists/*

# Set working directory BEFORE copy/install
WORKDIR /var/www/html

# Copy only composer files first (for better cache)
COPY composer.json composer.lock ./

# Install dependencies
RUN if [ "$APP_ENV" = "prod" ]; then \
      COMPOSER_CACHE_DIR=/tmp composer install --no-dev --optimize-autoloader; \
    else \
      composer install; \
    fi \
    && composer dump-autoload --optimize

# Now copy the rest of the project
COPY . .

# Fix ownership and permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Copy Apache config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 80

# Copy entrypoint script and make it executable
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Start Apache via entrypoint script
CMD ["/usr/local/bin/entrypoint.sh"]

# Start Apache
#CMD ["apache2-foreground"]