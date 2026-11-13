FROM php:7.4-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

COPY docker/php/php.ini /usr/local/etc/php/conf.d/payroll.ini
COPY www/ /var/www/html/
