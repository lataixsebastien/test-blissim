FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        unzip \
        ${PHPIZE_DEPS} \
    && docker-php-ext-install \
        intl \
        pdo \
        pdo_pgsql \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY backend/ .

RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist; fi

COPY docker/backend/docker-entrypoint.sh /usr/local/bin/backend-entrypoint
RUN chmod +x /usr/local/bin/backend-entrypoint

EXPOSE 9000

ENTRYPOINT ["backend-entrypoint"]
CMD ["php-fpm"]
