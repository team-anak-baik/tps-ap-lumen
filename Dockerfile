FROM php:8-apache

RUN a2enmod rewrite

WORKDIR /app

COPY ./composer.json .
COPY ./composer.lock .

RUN apt-get update && \
    apt-get install -y git unzip libpq-dev && \
    docker-php-ext-install pdo pdo_mysql && \
    docker-php-ext-install pdo pdo_pgsql && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer install --no-scripts --no-autoloader && \
    rm -rf /var/lib/apt/lists/*

COPY . .

RUN composer dump-autoload

EXPOSE 8003