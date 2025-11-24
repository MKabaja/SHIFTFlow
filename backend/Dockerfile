FROM php:8.2-fpm

# ========== INSTALACJA ZALEŻNOŚCI SYSTEMOWYCH ==========
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zlib1g-dev \
    default-mysql-client \
    git \
    curl \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# ========== INSTALACJA ROZSZERZEŃ PHP ==========
RUN docker-php-ext-install pdo_mysql zip

# ========== INSTALACJA COMPOSER ==========
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# ========== KONFIGURACJA NGINX ==========
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/sites-available/default

# ========== KONFIGURACJA SUPERVISOR ==========
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ========== USTAWIENIE KATALOGU ROBOCZEGO ==========
WORKDIR /var/www

# ========== KOPIOWANIE PLIKÓW APLIKACJI ==========
COPY . .

# ========== INSTALACJA ZALEŻNOŚCI COMPOSER ==========
RUN composer install --no-interaction --prefer-dist

# ========== USTAWIENIE UPRAWNIEŃ ==========
RUN chown -R www-data:www-data /var/www

# ========== EXPONOWANIE PORTÓW ==========
EXPOSE 8000 9000

# ========== URUCHOMIENIE SUPERVISOR (zarządza PHP-FPM i Nginx) ==========
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
