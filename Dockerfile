# ControlPass - PHP 8.3 / Apache / Railway
FROM php:8.3-apache

# Bibliotecas necesarias para GD, ZIP, Intl y mbstring.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
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
    && rm -rf /var/lib/apt/lists/*

# Habilitar .htaccess y mod_rewrite.
RUN a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
COPY . .

# Crear archivos de configuración a partir de plantillas sin credenciales.
RUN test -f config/conexion.php.example \
    && test -f config/mail.php.example \
    && cp config/conexion.php.example config/conexion.php \
    && cp config/mail.php.example config/mail.php

# XML/DOM/XMLReader/XMLWriter ya vienen habilitadas en la imagen oficial.
# Verificarlo, junto con las extensiones compiladas, antes de Composer.
RUN php -r 'foreach (["gd", "pdo_mysql", "mbstring", "dom", "xml", "xmlreader", "xmlwriter", "zip", "intl"] as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Missing PHP extension: $ext\n"); exit(1); } }'

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && composer check-platform-reqs --no-dev

EXPOSE 80
CMD ["apache2-foreground"]
