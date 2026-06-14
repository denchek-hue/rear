FROM php:8.4-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

COPY . /var/www/html/

RUN a2enmod rewrite

EXPOSE ${PORT:-80}

CMD bash -c "sed -i 's/80/${PORT:-80}/g' /etc/apache2/ports.conf && apache2-foreground"
