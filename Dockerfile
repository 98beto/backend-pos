FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    gettext \
    libpq \
    oniguruma \
    postgresql-libs \
    libxml2 \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpq-dev \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-install \
    mbstring \
    opcache \
    pdo_pgsql \
    xml \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN mkdir -p \
    /run/nginx \
    /var/lib/nginx/tmp/client_body \
    /var/log/supervisor \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /run/nginx /var/lib/nginx /var/log/nginx /var/log/supervisor

COPY .docker/railway/nginx.conf /etc/nginx/http.d/default.conf
COPY .docker/railway/supervisord.conf /etc/supervisord.conf
COPY .docker/railway/start-container /usr/local/bin/start-container

RUN chmod +x /usr/local/bin/start-container

EXPOSE 8080

CMD ["start-container"]
