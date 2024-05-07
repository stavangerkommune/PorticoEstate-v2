FROM php:8-fpm

## uncomment the following lines to install php-pear from behind a proxy
#RUN pear config-set http_proxy ${http_proxy} &&\
#    pear config-set php_ini $PHP_INI_DIR/php.ini


RUN apt-get update && apt-get install -y libpq-dev zlib1g-dev libzip-dev libxslt-dev \
	&& docker-php-ext-install pdo_pgsql xsl \
	&& pecl install xdebug \
	&& docker-php-ext-enable xdebug
RUN echo 'xdebug.mode=debug' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.client_host=host.docker.internal' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.client_port=9004' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.start_with_request=yes' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
#RUN echo 'xdebug.mode=debug,develop' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini