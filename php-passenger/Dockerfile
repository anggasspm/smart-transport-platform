FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        mysqli \
        sockets

RUN pecl channel-update pecl.php.net && pecl install apcu && docker-php-ext-enable apcu
RUN echo "apc.enable_cli=1" >> /usr/local/etc/php/php.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --optimize-autoloader --no-interaction --no-progress --ignore-platform-reqs

COPY . .

RUN mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar \
    && chmod -R 777 writable/

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php", "spark", "serve", "--host", "0.0.0.0", "--port", "8000"]