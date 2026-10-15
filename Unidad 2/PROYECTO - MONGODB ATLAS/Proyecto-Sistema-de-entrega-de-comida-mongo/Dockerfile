FROM php:8.2-apache

# 1. Instalar dependencias del sistema y librerías de compilación
RUN apt-get update && apt-get install -y \
    libssl-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install gd zip \
    && a2enmod rewrite headers deflate expires \
    && rm -rf /var/lib/apt/lists/*

# 2. Instalar Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Habilitar AllowOverride All en Apache y configurar php.ini de producción (igualando output_buffering de XAMPP)
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && printf "output_buffering = 4096\ndisplay_errors = Off\nlog_errors = On\ndate.timezone = America/Mexico_City\n" > /usr/local/etc/php/conf.d/snackver.ini

# 4. Copiar el código fuente del proyecto al directorio web de Apache
COPY . /var/www/html/

# 5. Instalar dependencias de PHP (MongoDB SDK, PHPMailer, Cloudinary, Dotenv) en Back-end/vendor
RUN composer install --working-dir=/var/www/html/Back-end --no-dev --optimize-autoloader --no-interaction

# 6. Asegurar permisos correctos para el usuario de Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 7. Puerto estándar HTTP
EXPOSE 80

CMD ["apache2-foreground"]

