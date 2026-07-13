FROM php:8.2-fpm-alpine3.19

LABEL org.opencontainers.image.title="Resort Voucher System"
LABEL org.opencontainers.image.description="PHP-FPM image for resort-web-qr Laravel 12"
LABEL org.opencontainers.image.vendor="Pawbxj"

ARG APP_ENV=production
ARG APP_DEBUG=false

ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=${APP_DEBUG} \
    COMPOSER_ALLOW_SUPERUSER=1

# ---- System dependencies & PHP extensions -----------------------------------
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    openssl-dev \
    linux-headers \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    mbstring \
    xml \
    bcmath \
    intl \
    gd \
    zip \
    sockets \
    pcntl \
    opcache \
  && pecl install redis \
  && docker-php-ext-enable redis opcache \
  && rm -rf /var/cache/apk/* /tmp/*

# ---- Composer ---------------------------------------------------------------
COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

# ---- Application code & dependencies ----------------------------------------
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-ansi \
  && rm -rf /root/.composer/cache

COPY . .

RUN set -eux \
  && mkdir -p storage/framework/{sessions,views,cache,testing} \
             storage/logs bootstrap/cache public/uploads \
  && php artisan storage:link --quiet --no-ansi 2>/dev/null || true \
  && php artisan config:cache --quiet --no-ansi \
  && php artisan route:cache --quiet --no-ansi \
  && php artisan view:cache --quiet --no-ansi \
  && php artisan event:cache --quiet --no-ansi 2>/dev/null || true

# ---- Permissions & hardening ------------------------------------------------
RUN set -eux \
  && chown -R www-data:www-data storage bootstrap/cache public/uploads \
  && chmod -R 750 storage bootstrap/cache public/uploads \
  && find . -type f \( -name ".env*" -o -name "artisan" \) -exec chmod 640 {} \; \
  && rm -rf \
    /tmp/* \
    /var/cache/apk/* \
    /root/.composer \
    tests/ \
    docker-compose.yml 2>/dev/null || true

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

STOPSIGNAL SIGQUIT

USER www-data

ENTRYPOINT ["docker-entrypoint.sh"]
