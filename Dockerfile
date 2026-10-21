FROM php:8.4-cli-bookworm AS dependencies
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libsqlite3-dev libonig-dev libxml2-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install -j4 pdo_sqlite mbstring dom intl gd pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

FROM node:22-bookworm-slim AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY --from=dependencies /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY --from=dependencies /var/www/html/vendor/livewire/livewire/src/Features/SupportPagination/views ./vendor/livewire/livewire/src/Features/SupportPagination/views
RUN npm run build

FROM dependencies
COPY . .
COPY --from=assets /build/public/build ./public/build
RUN composer dump-autoload --optimize \
    && touch .env \
    && mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
ENV COMPOSER_HOME=/tmp/posts-composer
EXPOSE 8000
ENTRYPOINT ["sh", "docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
