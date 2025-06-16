FROM php:8.3-apache

# Enable Apache rewrite module early
RUN a2enmod rewrite

# Install required libs and PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install intl mbstring zip pdo pdo_mysql \
    && composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

# Copy your Symfony project files to /var/www/html (Apache default)
COPY . /var/www/html/

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