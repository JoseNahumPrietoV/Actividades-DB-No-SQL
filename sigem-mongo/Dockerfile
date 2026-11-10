FROM php:8.4-apache

# 1. Habilitar mod_rewrite para las URLs amigables de Laravel
RUN a2enmod rewrite

# 2. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# 3. Instalar extensiones PHP base
RUN docker-php-ext-configure intl \
    && docker-php-ext-install zip intl pdo pdo_mysql

# 4. Instalar extensión de MONGODB (ESTO ES LO MÁS IMPORTANTE)
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# 5. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Configurar Apache para que apunte a la carpeta "public" de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 7. Copiar todos los archivos del proyecto al contenedor
WORKDIR /var/www/html
COPY . .

# 8. Instalar dependencias de Laravel
RUN composer install --optimize-autoloader --no-dev --ignore-platform-req=ext-mongodb

# 9. Asignar permisos correctos a las carpetas de caché
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Optimizar Filament y Laravel al arrancar
CMD php artisan optimize:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan filament:optimize \
    && apache2-foreground
