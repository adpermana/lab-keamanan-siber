FROM php:7.4-apache

LABEL description="SIMPEG - Aplikasi rentan untuk pembelajaran keamanan siber"

# Install ekstensi MySQL (mysqli)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Aktifkan mod_rewrite (untuk bahan pembelajaran .htaccess nantinya)
RUN a2enmod rewrite

# Copy semua file aplikasi ke document root
COPY . /var/www/html/

# Set permission untuk uploads
RUN chmod -R 777 /var/www/html/uploads/

# Copy custom php.ini untuk development
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
