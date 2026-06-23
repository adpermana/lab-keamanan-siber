FROM php:7.4-apache

RUN echo "display_errors = Off" > /usr/local/etc/php/conf.d/custom.ini

# Install docker CLI
RUN apt-get update && apt-get install -y curl ca-certificates gnupg sudo lsb-release && \
    curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add - && \
    echo "deb [arch=amd64] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list && \
    apt-get update && apt-get install -y docker-ce-cli && \
    rm -rf /var/lib/apt/lists/*

# Allow www-data to run docker commands without password
RUN echo "www-data ALL=(root) NOPASSWD: /usr/bin/docker *" >> /etc/sudoers

# Copy aplikasi
COPY index.php /var/www/html/index.php
COPY lab.php /var/www/html/lab.php
