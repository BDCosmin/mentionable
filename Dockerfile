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

# Set working directory
WORKDIR /var/www/html

# Copy entire app (so Composer sees full codebase before installing)
COPY . .

# Install dependencies *after* full app is present
RUN if [ "$APP_ENV" = "prod" ]; then \
      rm -rf vendor && \
      composer install --no-dev --optimize-autoloader; \
    else \
      composer install; \
    fi \
    && composer dump-autoload --optimize

# Set permissions (important for Symfony cache & logs)
RUN mkdir -p var \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Copy Apache config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]