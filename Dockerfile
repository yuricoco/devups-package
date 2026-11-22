# Image de developpement (optionnelle) pour devups - voir la section
# "Installation avec Docker" du README.md. Ne remplace pas une installation
# PHP classique, c'est une alternative pour isoler la version de PHP du
# systeme hote (cf. TODO.md, ETAPE 5.2 (v8.0) - autonomie PHP/Docker).
ARG PHP_VERSION=8.3
FROM composer:2 AS composer

FROM php:${PHP_VERSION}-cli-bookworm

# Extensions alignees sur .github/workflows/tests.yml (mbstring, pdo_mysql,
# gd, zip, intl) + dependances systeme necessaires a leur compilation.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mbstring pdo_mysql gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app
