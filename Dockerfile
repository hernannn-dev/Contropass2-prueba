FROM php:8.5-apache

# MySQL, PHP y reglas de rutas de ControlPass.
RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . /var/www/html/

# Archivos de configuración sin credenciales incorporadas.
RUN test -f config/conexion.php.example \
    && test -f config/mail.php.example \
    && cp config/conexion.php.example config/conexion.php \
    && cp config/mail.php.example config/mail.php \
    && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Railway suele asignar PORT; Apache debe escuchar el mismo puerto.
ENV PORT=8080
EXPOSE 8080
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
