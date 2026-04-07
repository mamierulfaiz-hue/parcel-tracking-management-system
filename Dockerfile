# Use a PHP image that includes Nginx
FROM richarvey/nginx-php-fpm:3.1.6

# Copy all your Laravel files into the server
COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader

# Set the working folder
WORKDIR /var/www/html

# Set the web root to Laravel's public folder
ENV WEBROOT /var/www/html/public

# Allow the server to use port 80
EXPOSE 80

# Start the server
CMD ["/start.sh"]