FROM php:8.1

RUN apt-get update && apt-get install -y unzip git

RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

RUN echo "xdebug.coverage_enable" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer/composer:2-bin /composer /usr/bin/composer

ARG UID
ARG GID

RUN usermod -u ${UID} www-data; groupmod -g ${GID} www-data

USER www-data
