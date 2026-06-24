# On part d'une image PHP officielle stable avec Apache
FROM php:8.2-apache

# Installer les extensions PHP nécessaires si besoin (ex: curl pour l'API Rukovoditel)
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Install the required MySQL driver extension for PDO
RUN docker-php-ext-install pdo_mysql

# Activer le module de réécriture d'Apache (toujours utile)
RUN a2enmod rewrite

# Copier le code de notre application dans le dossier web d'Apache
COPY . /var/www/html/
WORKDIR /var/www/html/

# Donner les bons droits d'accès aux fichiers
RUN chown -R www-data:www-data /var/www/html

# L'application écoutera sur le port 80
EXPOSE 80