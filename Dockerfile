FROM php:8.2-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    unzip \
    libmagickwand-dev --no-install-recommends

# Instalar nodejs para utilizar npm.
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

#test postgres
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install -j$(nproc) pdo_pgsql

RUN echo "extension=pdo_pgsql.so" >> /usr/local/etc/php/conf.d/postgres.ini


RUN echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/docker-fpm.ini
RUN echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/docker-fpm.ini
RUN echo "memory_limit=1024M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Establecer la configuración de la memoria
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install mbstring exif pcntl bcmath

RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick

#opcache
RUN docker-php-ext-install opcache
#COPY docker-compose/php/opcache.ini /usr/local/etc/php/conf.d/

#Eliminar el postgres.ini para no tener problemas al usar el pdo_pgsql
RUN rm /usr/local/etc/php/conf.d/postgres.ini


# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

RUN apt-get update && apt-get install -y libzip-dev zip && docker-php-ext-install zip

USER root
RUN echo 'johan ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
USER $user

