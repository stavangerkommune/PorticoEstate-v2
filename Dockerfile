#FROM php:8-alpine

#RUN apk --no-cache add postgresql-dev --repository=http://dl-cdn.alpinelinux.org/alpine/edge/main && docker-php-ext-install pdo_pgsql
#RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
#    && apk add --no-cache postgresql-dev --repository=http://dl-cdn.alpinelinux.org/alpine/edge/main\
#    && pecl install pdo_pgsql \
 #   && docker-php-ext-enable pdo_pgsql \
 #   && apk del .build-deps

FROM php:8-fpm

RUN apt-get update && apt-get install -y libpq-dev zlib1g-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug
RUN echo 'xdebug.mode=debug' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.client_host=host.docker.internal' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.client_port=9003' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.start_with_request=yes' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo 'xdebug.mode=debug,develop' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini