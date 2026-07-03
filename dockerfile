FROM php:8.2-apache

# Enable mysqli and pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy all files to Apache's web root
COPY . /var/www/html/

EXPOSE 80