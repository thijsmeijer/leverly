FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    bash \
    git \
    icu-dev \
    libzip-dev \
    postgresql-dev \
  && apk add --no-cache --virtual .php-build-deps $PHPIZE_DEPS \
  && docker-php-ext-install \
    intl \
    pdo_pgsql \
    zip \
  && pecl install pcov \
  && docker-php-ext-enable pcov \
  && apk del .php-build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_CACHE_DIR=/tmp/composer-cache

WORKDIR /workspace/apps/api
