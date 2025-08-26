FROM php:8.4-fpm-bookworm AS builder

RUN mkdir -p \
        /app \
        /var/run/php \
    && rm -rf /var/www \
    && groupmod -g 65532 www-data \
    && usermod -u 65532 -g 65532 www-data \
    && chown www-data:www-data /var/run/php \
    && chmod 0700 /var/run/php

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libonig-dev \
        libpq-dev \
        libssl-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        opcache \
        pdo_pgsql \
        pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Analysis tools
RUN apt-get install -y --no-install-recommends \
        procps \
        smem \
        time

RUN apt-get autoremove -y \
    && apt-get clean \
    && rm -rf \
        /var/lib/apt/lists/* \
        /var/tmp/* \
        /tmp/*

COPY --from=docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer


FROM builder AS dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Output directory for Xdebug cachegrind files
RUN mkdir /tmp/profiles \
    && chown -R www-data:www-data /tmp/profiles

# COPY ./config/debug/xdebug.ini ${PHP_INI_DIR}/conf.d/xdebug.ini   // alternative to bind mount

USER www-data
ENTRYPOINT ["/usr/local/sbin/php-fpm"]
CMD []


FROM builder AS builder-prod

USER root

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-dev --no-autoloader --prefer-dist
COPY . .
    # see.dockerignore

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction --strict-psr --strict-ambiguous

# Delete useless directories/files from vendor/ directory
RUN find vendor/ \
        -type d \( \
            -iname test \
            -o -iname tests \
            -o -iname doc \
            -o -iname docs \
            -o -iname examples \
        \) -prune -exec rm -rf '{}' + \
    && find vendor/ \
        -type f \( \
            -iname "*.md" \
            -o -iname "*.markdown" \
            -o -iname "*.editorconfig" \
            -o -iname ".gitignore" \
            -o -iname ".gitattributes" \
            # -o -iname "*.xml" \
            # -o -iname "*.txt" \
            # -o -iname "*.yml" \
            # -o -iname "*.yaml" \
            # -o -iname "*.rst" \
            # -o -iname "*.dist" \
            # -o -iname "*.ini" \
        \) -delete


FROM php:8.4-fpm-bookworm AS minimal-base

ENV HOME=/app \
    LC_ALL=C.UTF-8 \
    LANG=C.UTF-8

RUN mkdir -p \
        /app \
        /var/run/php \
    && rm -rf /var/www \
    && groupmod -g 65532 www-data \
    && usermod -u 65532 -g 65532 www-data \
    && chown www-data:www-data /var/run/php \
    && chmod 0700 /var/run/php

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfcgi-bin \
        libpq5 \
        libzip4

# libfcgi-bin   for PHP-FPM healthcheck
# libpq5        for pdo_pgsql, pgsql
# libzip4       for zip extension

# TODO: remove later. ps, top, free, smem, time
RUN apt-get install -y --no-install-recommends \
        procps \
        smem \
        time

RUN apt-get autoremove -y \
    && apt-get clean \
    && rm -rf \
        /var/lib/apt/lists/* \
        /var/tmp/* \
        /tmp/*

# PHP-FPM healthcheck, e.g. for Liveness check
RUN curl -sSL https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
        -o /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Copy extensions from `Builder` target
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/bin/docker-php-ext-* /usr/local/bin/


FROM minimal-base AS minimal-dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Output directory for Xdebug cachegrind files
RUN mkdir /tmp/profiles \
    && chown -R www-data:www-data /tmp/profiles

# COPY ./config/debug/xdebug.ini ${PHP_INI_DIR}/conf.d/xdebug.ini   // alternative to bind mount

USER www-data
ENTRYPOINT ["/usr/local/sbin/php-fpm"]
CMD []


FROM minimal-base AS prod

COPY --from=builder-prod /app /app

RUN chown -R www-data:www-data /app \
    && chmod -R 0400 /app \
    && find /app -type d -exec chmod 0500 {} +

USER www-data
ENTRYPOINT ["/usr/local/sbin/php-fpm"]
CMD []
