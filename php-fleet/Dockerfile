FROM php:8.2-cli

# Install system dependencies + PHP extensions untuk CI4
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files dulu untuk memanfaatkan Docker layer cache
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true

# Copy seluruh aplikasi
COPY . .

# Buat writable directories dengan permission yang benar
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
    && chmod -R 777 writable/

# Entrypoint script untuk generate .env dari Docker env vars lalu jalankan spark
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8001

HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
  CMD curl -f http://localhost:8001/health || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php", "spark", "serve", "--host", "0.0.0.0", "--port", "8001"]
