FROM php:7.4-fpm-alpine3.13

################################
###    FPM BASE LAYER        ###
################################

# https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

# make sure you can use HTTPS
RUN apk --update add ca-certificates

# persistent / runtime deps
RUN apk update \
    && apk add --no-cache --virtual .persistent-deps \
        git \
		curl \
		wget \
		tar \
		libressl \
        freetype \
        openssl

# install and remove building packages
RUN install-php-extensions \
    apcu \
    opcache \
    gd \
    exif \
    intl \
    pcntl \
    pdo_pgsql \
    redis \
    sockets \
    uuid \
    zip

EXPOSE 9000
