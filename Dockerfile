FROM php:8.1-apache

# Install SQLite and enable PDO SQLite
RUN apt-get update && apt-get install -y \
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

# Make upload directory writable
RUN mkdir -p /var/www/html/uploads /var/www/html/tmp \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/tmp

# Make sure database directory is writable
RUN chown -R www-data:www-data /var/www/html/config

# Set working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]