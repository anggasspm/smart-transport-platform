#!/bin/sh
# docker-entrypoint.sh
# Generates /var/www/html/.env from Docker environment variables,
# then executes the CMD passed to the container.
set -e

ENV_FILE="/var/www/html/.env"

# Only write .env if it doesn't already exist (allows bind-mount override).
if [ ! -f "$ENV_FILE" ]; then
    echo "==> Generating .env from Docker environment variables..."

    cat > "$ENV_FILE" <<EOF
CI_ENVIRONMENT=${CI_ENVIRONMENT:-production}

app.baseURL=http://localhost:8001/

# Database â€” CodeIgniter 4 format
database.default.hostname=${DB_HOST:-mysql}
database.default.database=${DB_NAME:-smarttransport}
database.default.username=${DB_USER:-root}
database.default.password=${DB_PASS:-rootpass}
database.default.DBDriver=MySQLi
database.default.port=${DB_PORT:-3306}

# RabbitMQ
RABBITMQ_HOST=${RABBITMQ_HOST:-rabbitmq}
RABBITMQ_PORT=${RABBITMQ_PORT:-5672}
RABBITMQ_USER=${RABBITMQ_USER:-guest}
RABBITMQ_PASS=${RABBITMQ_PASS:-guest}
RABBITMQ_EXCHANGE=${RABBITMQ_EXCHANGE:-city.events}
EOF

    echo "==> .env generated at $ENV_FILE"
else
    echo "==> .env already exists, skipping generation."
fi

# Hand off to CMD (e.g. php spark serve ...)
exec "$@"
