FROM richarvey/nginx-php-fpm:3.1.6

# 1. Copy all project files
COPY . /var/www/html

# 2. Set working directory
WORKDIR /var/www/html

# 3. Install dependencies 
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 4. Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 5. Configure the web server
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
ENV SKIP_COMPOSER 1
ENV PHP_ERRORS_STDERR 1

EXPOSE 6767

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# THE FIX: wait for MySQL, generate an app key, migrate tables, then start nginx/php-fpm
CMD ["/usr/local/bin/docker-entrypoint.sh"]