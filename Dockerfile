FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    npm \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . .

# Install project dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure storage, bootstrap/cache, and database are writable
RUN chown -R www-data:www-data storage bootstrap/cache database
RUN chmod -R 777 database

EXPOSE 8000

# Default command: start multiple processes
COPY start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
