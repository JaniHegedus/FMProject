# Dockerfile
FROM php:8.2-fpm

# Install system dependencies: git, unzip, curl, sqlite libs, supervisor, cron
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libsqlite3-dev \
    supervisor \
    cron \
    && docker-php-ext-install pdo pdo_sqlite

# Set working directory
WORKDIR /var/www/html

# Copy in our Supervisor config and cron file
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY crontab /etc/cron.d/laravel

# Make the cron file readable and register it
RUN chmod 0644 /etc/cron.d/laravel \
    && crontab /etc/cron.d/laravel \
    && touch /var/log/cron.log

# Expose port if you want to use `artisan serve`
EXPOSE 8000

# The main CMD: run Supervisor in the foreground
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
