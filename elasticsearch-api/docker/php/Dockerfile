#php version import
FROM php:8.2-cli

# Instalar dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl \
    && docker-php-ext-install pdo_mysql zip

#work directory
WORKDIR /app

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar archivos de Laravel
COPY . .

# Instalar dependencias de Laravel
RUN composer install --no-interaction --no-scripts --no-progress

# Exponer puerto
EXPOSE 8000

# Comando para iniciar Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000
