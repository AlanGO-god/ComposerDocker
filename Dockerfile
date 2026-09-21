FROM php:8.3-cli

# Herramientas del sistema y librerías necesarias para las extensiones de PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip \
    && rm -rf /var/lib/apt/lists/*

# Copiamos el binario de Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
EXPOSE 8000