
FROM php:8.4-cli

# Paket sistem dan ekstensi PHP yang diperlukan Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-configure gd \
       --with-freetype \
       --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
       pdo_mysql \
       mbstring \
       bcmath \
       exif \
       pcntl \
       gd \
       zip \
       intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Mengambil Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Menyalin kode backend
COPY . .

# Memasang dependency produksi Laravel
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    && mkdir -p storage/framework/cache/data \
       storage/framework/sessions \
       storage/framework/views \
       storage/logs \
       bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# User non-root untuk menjalankan aplikasi
USER www-data

EXPOSE 10000

# Port disediakan oleh Render
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
