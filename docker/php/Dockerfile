FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libicu-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql intl zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
