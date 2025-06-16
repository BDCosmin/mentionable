FROM php:8.3-apache

# Enable Apache rewrite module early
RUN a2enmod rewrite

# Install required libs and PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install intl mbstring zip pdo pdo_mysql

# Copy composer binary from official composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy your Symfony project files to /var/www/html (Apache default)
COPY . /var/www/html/

# Install PHP dependencies inside your project folder
RUN composer install --no-dev --optimize-autoloader --working-dir=/var/www/html -vvv
RUN ls -l /var/www/html/vendor

# Copy custom Apache config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Create var folder if missing (optional safety)
RUN mkdir -p var

# Set ownership for entire project folder (including var)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]