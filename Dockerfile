FROM php:8.3-fpm-alpine AS php_base

RUN apk add --no-cache \
    git \
    bash \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    nginx \
    supervisor \
    nodejs \
    npm

RUN docker-php-ext-install intl mbstring zip pdo pdo_mysql

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY package.json package-lock.json* ./

RUN npm install
RUN npm run build

COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache

COPY ./deploy/nginx.conf /etc/nginx/nginx.conf
COPY ./deploy/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

