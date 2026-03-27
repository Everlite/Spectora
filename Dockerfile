FROM php:8.4-apache

# Enable Apache mod_rewrite for Laravel
RUN a2enmod rewrite

# Update Apache DocumentRoot to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# System dependencies
RUN apt-get update && apt-get install -y \
    cron \
    supervisor \
    zip \
    unzip \
    git \
    curl \
    gnupg \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    sqlite3 \
    libsqlite3-dev \
    # Chromium and its dependencies for Lighthouse
    chromium \
    libnss3 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libxcomposite1 \
    libxdamage1 \
    libxrandr2 \
    libgbm1 \
    libasound2 \
    libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js and Lighthouse
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g lighthouse

# PHP Extensions
RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip

# Configure Cron for Laravel Scheduler
COPY ./docker/laravel.cron /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron
RUN crontab /etc/cron.d/laravel-cron

# Copy Supervisor configuration
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Pre-create directories for volumes and set permissions
RUN mkdir -p /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure .env exists and has an APP_KEY
RUN if [ ! -f .env ]; then cp .env.example .env; fi \
    && if [ -z "$(grep APP_KEY .env | cut -d'=' -f2)" ]; then php artisan key:generate --force; fi


# Final permission check after composer/env actions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Expose port 80
EXPOSE 80

# Start Supervisor (which starts Apache and Cron)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
