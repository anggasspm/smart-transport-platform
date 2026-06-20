#!/bin/sh
set -e
ENV_FILE="/var/www/html/.env"
if [ ! -f "$ENV_FILE" ]; then
    echo "==> Generating .env..."
    cat > "$ENV_FILE" <<EOF
CI_ENVIRONMENT=${CI_ENVIRONMENT:-production}
app.baseURL=http://localhost:8002/
database.default.hostname=${DB_HOST:-mysql}
database.default.database=${DB_NAME:-smarttransport}
database.default.username=${DB_USER:-root}
database.default.password=${DB_PASS:-rootpass}
database.default.DBDriver=MySQLi
database.default.port=${DB_PORT:-3306}
RABBITMQ_HOST=${RABBITMQ_HOST:-rabbitmq}
RABBITMQ_PORT=${RABBITMQ_PORT:-5672}
RABBITMQ_USER=${RABBITMQ_USER:-admin}
RABBITMQ_PASS=${RABBITMQ_PASS:-adminpass}
EOF
    echo "==> .env generated"
else
    echo "==> .env already exists"
fi
exec "$@"
