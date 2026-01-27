FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    zip \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini
RUN echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

# Set permissions
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

USER www

EXPOSE 9000

CMD ["php-fpm"]
