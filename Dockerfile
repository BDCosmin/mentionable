FROM php:8.3-apache

# Activează modulul rewrite
RUN a2enmod rewrite

# Setează document root la /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Modifică configul apache să folosească acel document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Copiază tot proiectul în imagine
COPY . /var/www/html

# Setează permisiuni corecte
RUN chown -R www-data:www-data /var/www/html