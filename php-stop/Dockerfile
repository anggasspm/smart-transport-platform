FROM php:8.2-cli

RUN apt-get update && apt-get install -y     curl     unzip     && docker-php-ext-install pdo pdo_mysql     && apt-get clean     && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true

COPY . .

EXPOSE 8002

HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
  CMD curl -f http://localhost:8002/health || exit 1

CMD ["php", "-S", "0.0.0.0:8002", "-t", "public"]