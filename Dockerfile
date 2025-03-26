# ==============================
#  Etapa 1: Builder
# ==============================
FROM composer:2.5 AS builder

WORKDIR /app

# Copia composer.{json,lock} e instala dependencias (sin dev)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction

# Copia el resto del proyecto
COPY . .

# Optimiza el autoloader (opcional)
RUN composer dump-autoload --optimize

# ==============================
#  Etapa 2: Runtime
# ==============================
FROM php:8.2-fpm-alpine

# Instalar extensiones del sistema y de PHP
RUN apk add --no-cache \
    zip \
    libzip-dev \
    postgresql-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql zip

# Copiar archivo de configuración para forzar "listen = 0.0.0.0:9000"
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# Copiar el código y las dependencias instaladas desde la etapa "builder"
COPY --from=builder /app /var/www/html

# Ajustar permisos para www-data en storage y bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Exponer el puerto 9000 de PHP-FPM
EXPOSE 9000

# Comando principal
CMD ["php-fpm"]
