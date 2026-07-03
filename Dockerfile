FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy only the contents of api/ into the web root
COPY api/ /var/www/html/

RUN a2enmod rewrite

EXPOSE 80