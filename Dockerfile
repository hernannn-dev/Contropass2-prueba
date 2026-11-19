# ControlPass - PHP 8.3 / Apache / Railway
FROM php:8.3-apache

# Dependencias del sistema para las extensiones PHP requeridas por Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        pdo_mysql \
        mysqli \
        zip \
        intl \
        mbstring \
        dom \
        xml \
        xmlreader \
        xmlwriter \
    && rm -rf /var/lib/apt/lists/*

# Permitir reglas de reescritura del proyecto
RUN a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
COPY . .

# Crear configuraciones a partir de plantillas sin secretos
RUN test -f config/conexion.php.example \
    && test -f config/mail.php.example \
    && cp config/conexion.php.example config/conexion.php \
    && cp config/mail.php.example config/mail.php

# Verificar que las extensiones estén disponibles antes de instalar paquetes
RUN php -r 'foreach (["gd", "pdo_mysql", "mbstring", "dom", "xml", "xmlreader", "xmlwriter", "zip"] as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Missing PHP extension: $ext\n"); exit(1); } }'

# Instalar dependencias según composer.lock
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && composer check-platform-reqs --no-dev

EXPOSE 80
CMD ["apache2-foreground"]
