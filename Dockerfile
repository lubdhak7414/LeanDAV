FROM php:8.2-apache

RUN a2enmod rewrite headers

# Install required PHP extensions
RUN apt-get update && apt-get install -y libxml2-dev && \
    docker-php-ext-install fileinfo xmlwriter && \
    rm -rf /var/lib/apt/lists/*

COPY public/ /var/www/html/
COPY lib/ /var/www/lib/
COPY config.example.php /var/www/config.example.php

# Config is mounted as volume — never baked into image
RUN mkdir -p /var/www/data/.locks /var/www/data/.logs

VOLUME ["/var/www/data", "/var/www/config"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -sf http://localhost/ -o /dev/null || exit 1

EXPOSE 80