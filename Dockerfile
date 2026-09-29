FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod headers

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
WORKDIR /var/www/html

# Render provides PORT at runtime; local containers default to Apache port 80.
EXPOSE 80
CMD ["sh", "-c", "sed -i -E \"s/^Listen [0-9]+/Listen ${PORT:-80}/; s/\\*:[0-9]+/\\*:${PORT:-80}/\" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
