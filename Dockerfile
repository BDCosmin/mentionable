FROM php:8.3-fpm
WORKDIR /var/www/html
COPY . .
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite
RUN composer install --no-dev --optimize-autoloader
EXPOSE 8080
CMD ["php-fpm"]