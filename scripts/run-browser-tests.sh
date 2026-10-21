#!/bin/sh
set -eu
cleanup() {
  label=$(docker inspect -f '{{ index .Config.Labels "posts.browser-test" }}' posts-browser-check 2>/dev/null || true)
  if [ "$label" = true ]; then
    docker rm -f posts-browser-check >/dev/null 2>&1 || true
  fi
}
# Only remove the disposable container marked by this project's browser tests.
cleanup
trap cleanup EXIT
trap 'exit 130' INT TERM
npx playwright test "$@"
