# syntax=docker/dockerfile:1.7

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build

FROM php:8.3-fpm-bookworm

ENV APP_ROLE=web \
    APP_ENV=production \
    PHP_CLI_OPCACHE_ENABLE=1

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        nginx \
        zip \
        unzip \
        libcurl4-openssl-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        libpq-dev \
        libxml2-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        sockets \
        xml \
        zip \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /run/php /var/lib/nginx/body /var/log/nginx /var/www/html/storage \
    && chown -R www-data:www-data /run/php /var/lib/nginx /var/log/nginx /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock artisan ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY storage ./storage

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY . .
COPY --from=assets /app/public/build ./public/build
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php/conf.d/zz-app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/start-container.sh /usr/local/bin/start-container

RUN chmod +x /usr/local/bin/start-container \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && BROADCAST_CONNECTION=log php artisan package:discover --ansi

EXPOSE 80 8080

ENTRYPOINT ["/usr/local/bin/start-container"]
