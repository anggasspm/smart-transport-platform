#!/bin/sh
# docker-entrypoint.sh
# Generate .env dari Docker environment variables sebelum menjalankan CI4

set -e

ENV_FILE="/var/www/html/.env"

# Tulis .env dari env vars Docker (docker-compose.yml)
cat > "$ENV_FILE" << EOF
CI_ENVIRONMENT = ${CI_ENVIRONMENT:-production}

app.baseURL = http://0.0.0.0:8001/
app.indexPage =

# Database - CI4 format (dibaca langsung oleh framework)
database.default.hostname = ${DB_HOST:-mysql}
database.default.database = ${DB_NAME:-smarttransport}
database.default.username = ${DB_USER:-root}
database.default.password = ${DB_PASS:-rootpass}
database.default.DBDriver = MySQLi
database.default.port     = ${DB_PORT:-3306}

# RabbitMQ
RABBITMQ_HOST     = ${RABBITMQ_HOST:-rabbitmq}
RABBITMQ_PORT     = ${RABBITMQ_PORT:-5672}
RABBITMQ_USER     = ${RABBITMQ_USER:-admin}
RABBITMQ_PASS     = ${RABBITMQ_PASS:-adminpass}
RABBITMQ_EXCHANGE = ${RABBITMQ_EXCHANGE:-city.events}

SERVICE_NAME = fleet-service
EOF

echo "[fleet-service] .env generated from Docker environment variables"
echo "[fleet-service] DB_HOST=${DB_HOST:-mysql}, DB_NAME=${DB_NAME:-smarttransport}"
echo "[fleet-service] RABBITMQ_HOST=${RABBITMQ_HOST:-rabbitmq}"

# Jalankan command yang diberikan (php spark serve ...)
exec "$@"
