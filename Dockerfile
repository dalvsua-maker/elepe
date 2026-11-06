FROM php:8.3-apache

# Herramientas necesarias: OpenSSL (TLS para Atlas), git y unzip (para Composer)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libssl-dev git unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensión de MongoDB para PHP
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Composer, copiado desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache sirve solo la carpeta public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP en modo producción: los errores no se muestran en pantalla, solo se registran
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Apache escucha en el puerto que indique la plataforma (variable PORT)
ENV PORT=80
RUN sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/*.conf

# Código del proyecto dentro de la imagen
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
COPY . .