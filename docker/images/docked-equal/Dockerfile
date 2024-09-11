FROM php:7.4-apache
LABEL   Description="Latest version of eQual with Apache UID and GUID injection" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="1.0"
ARG apache_uid
ARG apache_gid
RUN apt-get update && apt-get install -y \
	gnupg2 \
    git \
    procps \
    netcat \
    vim \
	wget \
    && /usr/sbin/usermod -u $apache_uid www-data \
    && /usr/sbin/groupmod -g $apache_gid www-data
COPY ./mpm_prefork.conf /etc/apache2/mods-available/
COPY ./000-default.conf /etc/apache2/sites-available/
# install sqlsrv drivers
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - 
RUN curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
RUN apt-get update
RUN ACCEPT_EULA=Y apt-get -y --no-install-recommends install msodbcsql17 odbcinst=2.3.7 odbcinst1debian2=2.3.7 unixodbc-dev=2.3.7 unixodbc=2.3.7
RUN pecl install sqlsrv-5.10.1 pdo_sqlsrv-5.10.1
# install the PHP extensions we need
RUN set -ex; \
        \
        savedAptMark="$(apt-mark showmanual)"; \
        \
        apt-get update; \
        apt-get install -y --no-install-recommends \
				libfreetype6-dev \
                libjpeg-dev \
                libpng-dev \
                libwebp-dev \
                libtidy-dev \
                libzip-dev \
                libicu-dev \
				libonig-dev \
                zip \
        ; \
        \
        docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
        docker-php-ext-configure intl; \		
        docker-php-ext-install gd mysqli opcache zip tidy intl; \
		docker-php-ext-install mbstring; \
		docker-php-ext-enable sqlsrv pdo_sqlsrv mbstring; \
        \
# reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
        apt-mark auto '.*' > /dev/null; \
        apt-mark manual $savedAptMark; \
        ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
                | awk '/=>/ { print $3 }' \
                | sort -u \
                | xargs -r dpkg-query -S \
                | cut -d: -f1 \
                | sort -u \
                | xargs -rt apt-mark manual; \
        \
        apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
        rm -rf /var/lib/apt/lists/*
# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
                echo 'opcache.memory_consumption=256'; \
                echo 'opcache.interned_strings_buffer=64'; \
                echo 'opcache.max_accelerated_files=8000'; \
                echo 'opcache.revalidate_freq=60'; \
                echo 'opcache.enable_cli=1'; \
        } > /usr/local/etc/php/conf.d/opcache-recommended.ini
RUN a2enmod rewrite expires
VOLUME /var/www/html
CMD ["apache2-foreground"]