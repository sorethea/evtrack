FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies as root
USER root

# Install system packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    mariadb-client \
    libpng-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libfreetype6-dev \
    locales \
    libtidy-dev \
    libzip-dev \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    nano \
    curl

RUN apt-get update && apt-get install -y libicu-dev
# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath intl
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install nodejs and npm
RUN apt-get update && apt-get install -y nodejs npm
RUN npm install -g npm@9.5.0

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Use existing www group and create www user
RUN useradd -u 1000 -g www -ms /bin/bash www

# Copy application files and set ownership
COPY --chown=www:www . /var/www

# Install composer dependencies as www user
USER www
RUN composer install --no-interaction --optimize-autoloader

# Switch back to root for permission fixes
USER root
RUN chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Switch to application user for runtime
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
