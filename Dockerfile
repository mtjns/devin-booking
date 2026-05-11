FROM php:8.4-fpm-alpine AS php_base

RUN apk add --no-cache \
    git \
    bash \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    zip \
    unzip \
    curl \
    nginx \
    supervisor \
    nodejs \
    npm

RUN docker-php-ext-install intl mbstring zip pdo pdo_mysql

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install gd

WORKDIR /var/www/html

COPY . .

RUN mkdir -p bootstrap/cache storage/logs storage/framework/cache storage/framework/views storage/framework/sessions

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

RUN npm install
RUN npm run build

RUN chown -R www-data:www-data storage bootstrap/cache

COPY ./docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

COPY ./deploy/nginx.conf /etc/nginx/nginx.conf
COPY ./deploy/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

ENTRYPOINT ["/docker-entrypoint.sh"]

