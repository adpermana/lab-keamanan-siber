FROM php:7.4-apache

LABEL description="PhotoRec v3 - PHP 7.4 compatible version"

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy HTML files
COPY html/ /var/www/html/

# Set permissions for uploads
RUN chmod -R 777 /var/www/html/admin/uploads/

# Custom php.ini for development
RUN echo "display_errors = On" > /usr/local/etc/php/conf.d/custom.ini && \
    echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/custom.ini
