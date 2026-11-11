#ETAPA 1 PREPARANDO EL AMBIENTE PARA EL FRONTEND DE LA APLICACION
FROM node:22-alpine AS frontend

#Creando directorio en el contenedor 
WORKDIR /app

#copiando los archivos de dependencias al contenedor
COPY package*.json ./

# Instalando las dependencias exactas de package-lock.json
RUN npm ci
#Copiando el resto de los archivos al contenedor
COPY . .
# Construyendo los archivos CSS y JavaScript con Vite
RUN npm run build


#################################ETAPA 2 PREPARANDO BACKEND #####################################

FROM php:8.3-apache-bookworm AS app

COPY docker/php.ini /usr/local/etc/php/conf.d/99-oncosavi.ini

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libcurl4-openssl-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*


RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    mbstring \
    bcmath \
    intl \
    zip \
    gd \
    dom \
    xml \
    exif \
    pcntl \
    opcache \
    curl

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite



ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=composer:2 \
    /usr/bin/composer \
    /usr/bin/composer

WORKDIR /var/www/html

COPY . .

COPY --from=frontend \
    /app/public/build \
    ./public/build

RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache \
    && chmod -R 775 \
    storage \
    bootstrap/cache

EXPOSE 80

COPY docker/app-entrypoint.sh /usr/local/bin/oncosavi-entrypoint
RUN chmod +x /usr/local/bin/oncosavi-entrypoint
ENTRYPOINT ["oncosavi-entrypoint"]
CMD ["apache2-foreground"]
