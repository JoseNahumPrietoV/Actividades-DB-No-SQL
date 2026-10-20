FROM php:8.4-apache

# Instalar dependencias del sistema + extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-install zip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar la aplicación
COPY . /var/www/html/

# Instalar dependencias de Composer (ahora sí funcionará)
RUN composer install --no-dev --optimize-autoloader

# Configurar Apache para escuchar en todas las interfaces
RUN sed -i 's/Listen 80/Listen 0.0.0.0:80/g' /etc/apache2/ports.conf && \
    sed -i 's/:80/:80/g' /etc/apache2/sites-available/000-default.conf

# Habilitar mod_rewrite (útil para URLs amigables)
RUN a2enmod rewrite

# Deshabilitar todos los MPM y dejar solo prefork (php:apache usa prefork por defecto)
RUN a2dismod mpm_event mpm_worker || true && a2enmod mpm_prefork

# Script de entrada personalizado
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
