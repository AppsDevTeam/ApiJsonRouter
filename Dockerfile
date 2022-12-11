FROM php:7.4-cli

RUN apt-get update && apt-get install -y unzip

RUN curl --fail -sSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-source extract \
&& apt-get update  \
&& apt-get install -y libicu-dev && docker-php-ext-install intl \
&& pecl install xdebug && docker-php-ext-enable xdebug && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
&& docker-php-source delete