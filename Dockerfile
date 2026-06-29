# --- STEP 1 : The Builder ---
FROM php:8.2-apache AS builder

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# 1. Copy first the configuration files
COPY composer.json composer.lock* ./

# 2. Copy src folder for Composer to link utils.php and PSR-4
COPY src/ ./src/

# 3. Run the installation with the indexing of the files
RUN if [ -f "composer.json" ]; then \
    composer install --no-interaction --no-plugins --no-scripts --no-dev --optimize-autoloader --ignore-platform-reqs; \
    fi

# --- STEP 2 : The Final Production Image ---
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) curl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

RUN a2enmod rewrite

WORKDIR /var/www/html

# Get the vendor folder
COPY --from=builder /app/vendor /var/www/html/vendor
# Copy the rest of the code
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html
EXPOSE 80