FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set Apache document root to /var/www/html/api
ENV APACHE_DOCUMENT_ROOT=/var/www/html/api

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Copy the application
COPY . /var/www/html/

# Enable rewrite module
RUN a2enmod rewrite

EXPOSE 80