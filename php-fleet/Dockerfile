FROM php:8.2-cli

# ---------------------------------------------------------------------------
# 1. System dependencies
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# 2. PHP extensions required by CodeIgniter 4 + php-amqplib
# ---------------------------------------------------------------------------
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        mysqli \
        sockets

RUN pecl install apcu && docker-php-ext-enable apcu
RUN echo "apc.enable_cli=1" >> /usr/local/etc/php/php.ini
# ---------------------------------------------------------------------------
# 3. Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# 4. Working directory
# ---------------------------------------------------------------------------
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# 5. Install PHP dependencies
#    Copy composer files first for layer cache, then full source.
# ---------------------------------------------------------------------------
COPY composer.json composer.lock* ./
RUN composer install --optimize-autoloader --no-interaction --no-progress --ignore-platform-reqs

# ---------------------------------------------------------------------------
# 6. Copy full application source (overwrites composer files already there)
# ---------------------------------------------------------------------------
COPY . .

# ---------------------------------------------------------------------------
# 7. Create writable sub-directories and set permissions
# ---------------------------------------------------------------------------
RUN mkdir -p writable/cache \
             writable/logs \
             writable/session \
             writable/uploads \
             writable/debugbar \
    && chmod -R 777 writable/

# ---------------------------------------------------------------------------
# 8. Entrypoint script: generate .env from Docker env vars at runtime,
#    then start the CodeIgniter 4 dev server.
# ---------------------------------------------------------------------------
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8001

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php", "spark", "serve", "--host", "0.0.0.0", "--port", "8001"]
