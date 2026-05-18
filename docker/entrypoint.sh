#!/bin/sh
set -e

cd /app

# Re-assert writable permissions in case a fresh persistent volume was mounted
# over storage/ (mounted volumes start owned by root with restrictive perms).
chmod -R ug+w storage bootstrap/cache 2>/dev/null || true

# Decide framework warm-up based on the CMD we are about to exec.
#   frankenphp run ...           → API service: cache framework + run migrations
#   php artisan horizon          → Horizon: cache framework only (no migrations)
#   anything else (tinker, sh)   → no framework work
case "$1" in
	frankenphp)
		echo "[entrypoint] Caching framework state..."
		php artisan optimize

		echo "[entrypoint] Running migrations..."
		php artisan migrate --force --no-interaction
		;;
	php)
		if [ "$2" = "artisan" ] && [ "$3" = "horizon" ]; then
			echo "[entrypoint] Caching framework state for Horizon..."
			php artisan optimize
		fi
		;;
esac

exec "$@"
