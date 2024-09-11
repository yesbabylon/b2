FROM wordpress:6.6.1-php8.3
LABEL   Description="Latest version of Wordpress with Apache UID and GUID injection" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="1.0"

ARG apache_uid
ARG apache_gid

RUN apt-get update && apt-get install -y \
    git \
    procps \
    netcat-traditional \
    vim \
    && /usr/sbin/usermod -u $apache_uid www-data \
    && /usr/sbin/groupmod -g $apache_gid www-data

RUN docker-php-ext-configure pdo_mysql --with-pdo-mysql; \
    docker-php-ext-install pdo pdo_mysql;

COPY ./mpm_prefork.conf /etc/apache2/mods-available/