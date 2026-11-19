FROM php:8.3-apache

# Dependencias necesarias para GD, ZIP e Intl
RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql mysqli zip intl \
    && rm -rf /var/lib/apt/lists/*

# Apache y reglas .htaccess
RUN a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
COPY . .

# Configuraciones sin credenciales incrustadas en el repositorio
RUN test -f config/conexion.php.example \
    && test -f config/mail.php.example \
    && cp config/conexion.php.example config/conexion.php \
    && cp config/mail.php.example config/mail.php

# Verificar extensiones antes de instalar dependencias
RUN php -m | grep -qi '^gd$' \
    && php -m | grep -qi '^pdo_mysql$'

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

EXPOSE 80
CMD ["apache2-foreground"]
