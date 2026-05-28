#!/bin/sh
set -eu

# Default PORT to 8080 for local docker-run; Railway overrides with its own dynamic value.
: "${PORT:=8080}"
export PORT

# Substitute ${PORT} (and ONLY ${PORT}) into nginx config — other $ symbols (nginx vars like $uri) are left alone.
envsubst '${PORT}' < /etc/nginx/sites-available/default.template > /etc/nginx/sites-enabled/default

# Run pending migrations on container start. Idempotent — Laravel skips migrations already in the migrations table.
# Fail-fast: if migrate errors, container exits and Railway marks the deploy as failed (instead of serving a half-broken app).
php artisan migrate --force --no-interaction

# Start supervisord which orchestrates nginx + php-fpm.
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
