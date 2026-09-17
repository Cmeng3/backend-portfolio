FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libonig-dev libzip-dev unzip ca-certificates \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql mbstring zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/ports.conf /etc/apache2/ports.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/portfolio.ini
COPY docker/start.sh /usr/local/bin/portfolio-start
RUN sed -i 's/\r$//' /usr/local/bin/portfolio-start && chmod +x /usr/local/bin/portfolio-start

ENV PORT=10000
EXPOSE 10000
CMD ["/usr/local/bin/portfolio-start"]
