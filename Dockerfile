FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN a2enmod rewrite

# Web root is /var/www/html/public — app/ and config/ stay outside it (not web-accessible)
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN mkdir -p /var/www/html
COPY public/ /var/www/html/public/
COPY app/ /var/www/html/app/
COPY config/ /var/www/html/config/
COPY sql/ /var/www/html/sql/

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]

EXPOSE 80