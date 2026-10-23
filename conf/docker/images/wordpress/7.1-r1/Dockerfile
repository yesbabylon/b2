FROM wordpress:7.1.3-php8.5-apache

LABEL Description="WordPress 7.1 with Apache UID and GID injection" \
      Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
      License="Apache License 2.0" \
      Version="b2-wordpress-7.1-r1"

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git \
    procps \
    netcat-traditional \
    vim \
 && docker-php-ext-configure pdo_mysql --with-pdo-mysql \
 && docker-php-ext-install pdo pdo_mysql \
 && rm -rf /var/cache/apt/archives /var/lib/apt/lists/*

COPY ./mpm_prefork.conf /etc/apache2/mods-available/
