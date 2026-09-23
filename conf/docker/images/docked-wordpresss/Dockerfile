FROM wordpress:7.1.1-php8.5

LABEL Description="WordPress 7.1 with Apache UID and GID injection" \
      Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
      License="Apache License 2.0" \
      Version="1.0"

ARG apache_uid
ARG apache_gid

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git \
    procps \
    netcat-traditional \
    vim \
 && /usr/sbin/usermod -u "$apache_uid" www-data \
 && /usr/sbin/groupmod -g "$apache_gid" www-data \
 && docker-php-ext-configure pdo_mysql --with-pdo-mysql \
 && docker-php-ext-install pdo pdo_mysql \
 && rm -rf /var/cache/apt/archives /var/lib/apt/lists/*

COPY ./mpm_prefork.conf /etc/apache2/mods-available/
