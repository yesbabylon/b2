FROM wordpress:6.6.1-php8.3
LABEL   Description="WordPress 6.6 with Apache UID and GID injection" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="1.0"

RUN apt-get update && apt-get install -y \
    git \
    procps \
    netcat-traditional \
    vim

RUN docker-php-ext-configure pdo_mysql --with-pdo-mysql; \
    docker-php-ext-install pdo pdo_mysql;

COPY ./mpm_prefork.conf /etc/apache2/mods-available/
