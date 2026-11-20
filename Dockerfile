# Etapa 1: dependências PHP (para o build dos assets enxergar o vendor)
FROM composer:2 AS vendor
WORKDIR /app
COPY sipra-prot/composer.json sipra-prot/composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --ignore-platform-reqs

# Etapa 2: build dos assets (Vite)
FROM node:24-alpine AS assets
WORKDIR /app
COPY sipra-prot/ .
COPY --from=vendor /app/vendor ./vendor
RUN npm ci && npm run build
# Etapa 3: PHP 8.4 + Nginx
FROM serversideup/php:8.4-fpm-nginx
ENV SSL_MODE=off
WORKDIR /var/www/html
COPY --chown=www-data:www-data sipra-prot/ .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
RUN composer install --no-dev --optimize-autoloader --no-interaction
EXPOSE 8080
