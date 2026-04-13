FROM composer:2 AS composer-deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

FROM node:22-slim AS css-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY tailwind.config.js ./
COPY src/ ./src/
COPY include/ ./include/
COPY scp/ ./scp/
COPY ./*.php ./
RUN npx tailwindcss -i src/input.css -o styles/tickethub.css --minify \
    && npx tailwindcss -i src/input.css -o scp/css/tickethub-scp.css --minify

FROM php:8.4-apache-bookworm

ARG PHP_EXT_INSTALLER_VERSION=2.7.23
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/${PHP_EXT_INSTALLER_VERSION}/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends fonts-dejavu-core curl \
    && install-php-extensions mysqli pdo_mysql imap gd \
    && a2enmod rewrite headers \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN printf "display_errors=Off\nerror_reporting=E_ALL\nlog_errors=On\nerror_log=/tmp/php_errors.log\nshort_open_tag=On\nupload_max_filesize=64M\npost_max_size=128M\nmax_file_uploads=20\nmemory_limit=256M\n" \
    > /usr/local/etc/php/conf.d/tickethub.ini

WORKDIR /var/www/html

COPY . /var/www/html/
COPY --from=composer-deps /app/vendor /var/www/html/vendor
COPY --from=css-build /app/styles/tickethub.css /var/www/html/styles/tickethub.css
COPY --from=css-build /app/scp/css/tickethub-scp.css /var/www/html/scp/css/tickethub-scp.css

RUN cp include/th-config.sample.php include/th-config.php \
    && chmod +x docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -sf http://localhost/login.php || exit 1

EXPOSE 80
