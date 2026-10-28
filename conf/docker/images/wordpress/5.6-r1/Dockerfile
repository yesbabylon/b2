FROM wordpress:5.6.2-php7.4
LABEL   Description="WordPress 5.6 with Apache UID and GID injection" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="b2-wordpress:5.6-r1"

RUN apt-get update && apt-get install -y \
    git \
    procps \
    netcat \
    vim \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql \
    && docker-php-ext-install intl pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*
COPY ./mpm_prefork.conf /etc/apache2/mods-available/
