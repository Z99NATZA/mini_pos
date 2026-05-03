FROM php:8.3-cli-alpine

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY . .

RUN wget -qO /usr/local/bin/composer https://getcomposer.org/download/latest-stable/composer.phar \
    && chmod +x /usr/local/bin/composer \
    && composer install --no-dev --optimize-autoloader

RUN mkdir -p public/uploads/products public/uploads/users storage \
    && chmod -R 777 public/uploads storage

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
