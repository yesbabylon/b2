FROM php:7.2-apache
LABEL   Description="Magento 1.9 with Apache UID and GUID injection" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="1.0"
ARG apache_uid
ARG apache_gid
ENV MAGENTO_VERSION 1.9.3.8
ENV INSTALL_DIR /var/www/html
RUN cd /tmp && \
    curl https://codeload.github.com/OpenMage/magento-mirror/tar.gz/$MAGENTO_VERSION -o $MAGENTO_VERSION.tar.gz && \
    tar xvf $MAGENTO_VERSION.tar.gz && \
    mv magento-mirror-$MAGENTO_VERSION/* magento-mirror-$MAGENTO_VERSION/.htaccess $INSTALL_DIR && \
    chown -R www-data:www-data $INSTALL_DIR

RUN apt-get update && apt-get install -y \
    git \
    procps \
    netcat \
    vim \
    && /usr/sbin/usermod -u $apache_uid www-data \
    && /usr/sbin/groupmod -g $apache_gid www-data
COPY mpm_prefork.conf /etc/apache2/mods-available/

# install the PHP extensions we need
RUN set -ex; \
        \
        savedAptMark="$(apt-mark showmanual)"; \
        \
        apt-get update; \
        apt-get install -y --no-install-recommends \
                libjpeg-dev \
                libjpeg62-turbo-dev \
                libpng-dev \
                libtidy-dev \
                libmcrypt-dev \
                libzip-dev \
                libbz2-dev \
                zip \
                libicu-dev \
                libfreetype6-dev \
                libxml2-dev \
                libxslt-dev \
        ; \
        \
        pecl install mcrypt-1.0.1; \
        \
        docker-php-ext-configure gd --with-gd --with-png-dir=/usr --with-jpeg-dir=/usr --with-freetype-dir=/usr; \
        docker-php-ext-configure zip --with-libzip; \
        docker-php-ext-configure bcmath --enable-bcmath; \
        docker-php-ext-configure intl --enable-intl; \
        docker-php-ext-configure pcntl --enable-pcntl; \
        docker-php-ext-configure mysqli --with-mysqli; \
        docker-php-ext-configure pdo_mysql --with-pdo-mysql; \
        docker-php-ext-configure mbstring --enable-mbstring; \
        docker-php-ext-configure soap --enable-soap; \
        docker-php-ext-install gd bcmath intl pcntl exif iconv soap xmlrpc xsl mysqli pdo_mysql mbstring opcache bz2 zip tidy; \
        docker-php-ext-enable mcrypt; \
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
                echo 'opcache.memory_consumption=128'; \
                echo 'opcache.interned_strings_buffer=8'; \
                echo 'opcache.max_accelerated_files=4000'; \
                echo 'opcache.revalidate_freq=2'; \
                echo 'opcache.fast_shutdown=1'; \
                echo 'opcache.enable_cli=1'; \
        } > /usr/local/etc/php/conf.d/opcache-recommended.ini
RUN a2enmod rewrite expires
VOLUME /var/www/html
CMD ["apache2-foreground"]