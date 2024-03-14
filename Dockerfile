#FROM php:8-alpine

#RUN apk --no-cache add postgresql-dev --repository=http://dl-cdn.alpinelinux.org/alpine/edge/main && docker-php-ext-install pdo_pgsql
#RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
#    && apk add --no-cache postgresql-dev --repository=http://dl-cdn.alpinelinux.org/alpine/edge/main\
#    && pecl install pdo_pgsql \
 #   && docker-php-ext-enable pdo_pgsql \
 #   && apk del .build-deps

FROM php:8-fpm

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql