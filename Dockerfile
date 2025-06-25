FROM php:8.2-apache

# Install SQLite, Git, unzip (cho composer)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Run Composer install
RUN composer install

# Set permissions
RUN mkdir -p /var/www/html/uploads /var/www/html/tmp \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/tmp \
    && chown -R www-data:www-data /var/www/html/config

WORKDIR /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]