#!/bin/sh
set -eu
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s storage/app/local-key ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/local-key
        chmod 600 storage/app/local-key
    fi
    APP_KEY="$(cat storage/app/local-key)"
    export APP_KEY
fi
if [ "${DB_CONNECTION:-}" = sqlite ] && [ "${DB_DATABASE:-}" != ':memory:' ]; then
    touch "$DB_DATABASE"
fi
exec "$@"
