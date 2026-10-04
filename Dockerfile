FROM node:22-alpine AS frontend

WORKDIR /build/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && printf '<Directory %s>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        "$APACHE_DOCUMENT_ROOT" > /etc/apache2/conf-available/app.conf \
    && a2enconf app

COPY . /var/www/html/
COPY --from=frontend /build/public/admin/ /var/www/html/public/admin/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
