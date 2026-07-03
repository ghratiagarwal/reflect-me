FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /var/www/html/

# Change only the document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/api

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

EXPOSE 80