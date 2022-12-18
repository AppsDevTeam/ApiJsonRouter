FROM php:8.0-cli

RUN apt-get update && apt-get install -y unzip && curl --fail -sSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-source extract \
&& pecl install xdebug && docker-php-ext-enable xdebug && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
&& docker-php-source delete