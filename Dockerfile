FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && printf '<Directory %s>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        "$APACHE_DOCUMENT_ROOT" > /etc/apache2/conf-available/app.conf \
    && a2enconf app

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
