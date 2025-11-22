FROM php:8.2-fpm

# Instalacja zależności systemowych (libzip, mysql client, itp.)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zlib1g-dev \
    default-mysql-client \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Instalacja rozszerzeń PHP
RUN docker-php-ext-install pdo_mysql zip

# Instalacja Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Ustawienie katalogu roboczego
WORKDIR /var/www

# Kopiowanie plików aplikacji
COPY . .

# Instalacja zależności Composer
RUN composer install --no-interaction --prefer-dist

# Ustawienie uprawnień
RUN chown -R www-data:www-data /var/www

# Port dla PHP-FPM
EXPOSE 9000

# Uruchomienie PHP-FPM
CMD ["php-fpm"]
