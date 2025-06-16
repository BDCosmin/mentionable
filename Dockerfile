FROM php:8.3-apache
RUN a2enmod rewrite
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install intl mbstring zip pdo pdo_mysql
# Copy your Symfony project files to /var/www/html (Apache default)
COPY . /var/www/html/
# Copy custom Apache config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Set permissions (optional, depends on your setup)
RUN chown -R www-data:www-data /var/www/html/var

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]