FROM php:8.2-fpm

# Installer extensions PHP et outils nécessaires
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    zip \
    git \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir dossier de travail
WORKDIR /var/www/html

# Copier tout le projet dans le container
COPY . .

# Installer dépendances PHP via Composer
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Exposer le port 80
EXPOSE 80

# Lancer le serveur PHP intégré sur le port 80, dossier public
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]

