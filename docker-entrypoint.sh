#!/bin/sh
set -e

if [ -z "${DB_HOST:-}" ]; then
  if [ -n "${RAILWAY_ENVIRONMENT:-}" ]; then
    DB_HOST="mysql.railway.internal"
  else
    DB_HOST="db"
  fi
fi
DB_PORT="${DB_PORT:-3306}"

echo "Waiting for database at $DB_HOST:$DB_PORT..."
for i in $(seq 1 60); do
  if timeout 2 php -r '
    $h = getenv("DB_HOST"); $p = (int)(getenv("DB_PORT") ?: 3306);
    $c = @fsockopen($h, $p, $errno, $errstr, 2);
    if ($c) { fclose($c); exit(0); } exit(1);
  '; then
    echo "Database is ready."
    break
  fi
  echo "Waiting for database... ($i/60)"
  sleep 2
done

php /var/www/html/app/seed_admin.php

exec apache2-foreground