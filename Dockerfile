FROM php:8.2-apache

# Enable required Apache modules
RUN a2enmod rewrite headers

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    && docker-php-ext-install fileinfo xmlwriter dom \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www

# Copy application files
COPY public/ /var/www/html/
COPY lib/ /var/www/lib/
COPY config.example.php /var/www/config.example.php

# Create data directories with proper permissions
RUN mkdir -p /var/www/data/.locks /var/www/data/.logs && \
    chown -R www-data:www-data /var/www/data

# Block PHP execution in data directory
RUN echo 'Deny from all' > /var/www/data/.htaccess

# Override .htaccess for Docker (no HTTPS redirect inside container)
# HTTPS should be handled by reverse proxy (nginx, traefik, etc.)
RUN echo 'RewriteEngine On' > /var/www/html/.htaccess && \
    echo 'SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1' >> /var/www/html/.htaccess && \
    echo 'RewriteCond %{REQUEST_FILENAME} !-f' >> /var/www/html/.htaccess && \
    echo 'RewriteCond %{REQUEST_FILENAME} !-d' >> /var/www/html/.htaccess && \
    echo 'RewriteRule ^(.*)$ index.php [QSA,L]' >> /var/www/html/.htaccess && \
    echo 'Options -Indexes' >> /var/www/html/.htaccess && \
    echo 'AddDefaultCharset UTF-8' >> /var/www/html/.htaccess && \
    echo 'Header always set X-Content-Type-Options "nosniff"' >> /var/www/html/.htaccess

# Expose port
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -sf http://localhost/ -o /dev/null || exit 1

# Start Apache
CMD ["apache2-foreground"]
