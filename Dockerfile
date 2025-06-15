FROM php:8.3-apache

# Enable rewrite and allow .htaccess overrides
RUN a2enmod rewrite

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set root to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Modifying apache config
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Copy proj in image
COPY . /var/www/html

# Permisions set
RUN chown -R www-data:www-data /var/www/html