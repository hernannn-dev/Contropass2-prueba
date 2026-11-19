
FROM php:8.3-apache

# Instalar bibliotecas necesarias para PHP
RUN apt-get update && apt-get install -y \
    libfreetype-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    git \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        intl \
    && rm -rf /var/lib/apt/lists/*

# Habilitar reescritura de URLs
RUN a2enmod rewrite

# Permitir reglas de .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio del proyecto
WORKDIR /var/www/html

# Copiar archivos de ControlPass
COPY . .

# Crear archivos de configuracion
RUN test -f config/conexion.php.example \
    && test -f config/mail.php.example \
    && cp config/conexion.php.example config/conexion.php \
    && cp config/mail.php.example config/mail.php

# Instalar dependencias
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

# Verificar extensiones instaladas
RUN php -m | grep -i gd \
    && php -m | grep -i pdo_mysql

# Puerto Apache
EXPOSE 80

CMD ["apache2-foreground"]
