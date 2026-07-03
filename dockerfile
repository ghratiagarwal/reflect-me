FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /var/www/html/

RUN sed -ri -e 's!/var/www/html!/var/www/html/api!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/api!g' /etc/apache2/apache2.conf

EXPOSE 80