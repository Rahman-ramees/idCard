FROM php:8.1-apache

# Install GD extension and other dependencies
RUN apt-get update && \
    apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

# Copy your website files into the container
COPY . /var/www/html/

EXPOSE 80
