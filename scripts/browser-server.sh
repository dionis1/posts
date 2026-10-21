#!/bin/sh
set -eu
# Everything in this container, including the database, is disposable.
docker run --rm --stop-timeout=3 --label posts.browser-test=true --name posts-browser-check \
  -p 127.0.0.1:8010:8000 \
  -e APP_ENV=local -e APP_DEBUG=false \
  -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
  -e APP_URL=http://127.0.0.1:8010 \
  -e DB_CONNECTION=sqlite -e DB_DATABASE=/tmp/posts-browser.sqlite \
  -e SESSION_DRIVER=file -e CACHE_STORE=array -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array \
  posts-local:latest sh -c 'touch /tmp/posts-browser.sqlite && php artisan migrate --force --no-interaction && php tests/Browser/seed.php && php artisan serve --host=0.0.0.0 --port=8000 --no-reload' &
server_pid=$!
cleanup() {
  docker stop --time=3 posts-browser-check >/dev/null 2>&1 || true
  wait "$server_pid" 2>/dev/null || true
}
trap cleanup EXIT
trap 'exit 0' INT TERM
wait "$server_pid"
