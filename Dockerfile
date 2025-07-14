# Use an official PHP + Apache image
FROM php:8.1-apache

# Copy your website files to Apache's web root
COPY . /var/www/html/

# Expose port 80 to the outside
EXPOSE 80
