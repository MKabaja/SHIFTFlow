FROM php:8.2-fpm

# ========== SYSTEM DEPENDENCIES ==========
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zlib1g-dev \
    default-mysql-client \
    git \
    curl \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# ========== PHP EXTENSIONS ==========
RUN docker-php-ext-install pdo_mysql zip

# ========== COMPOSER ==========
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# ========== NGINX CONFIGURATION ==========
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/sites-available/default

# ========== SUPERVISOR CONFIGURATION ==========
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ========== WORKING DIRECTORY ==========
WORKDIR /var/www

# ========== APPLICATION FILES ==========
COPY . .

# ========== COMPOSER DEPENDENCIES ==========
RUN composer install --no-interaction --prefer-dist

# ========== PERMISSIONS ==========
RUN chown -R www-data:www-data /var/www

# ========== PORTS ==========
EXPOSE 8000 9000

# ========== START SUPERVISOR (manages PHP-FPM and Nginx) ==========
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
