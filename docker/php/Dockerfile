FROM php:8.0-fpm

WORKDIR /app

ENV PHP_IDE_CONFIG "serverName=application.local"

RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y git && \
    apt-get install -y librdkafka-dev

RUN pecl install xdebug-3.1.1 \
    && docker-php-ext-enable xdebug

RUN pecl install rdkafka \
    && docker-php-ext-enable rdkafka

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer