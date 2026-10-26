FROM php:8.5.12-apache-trixie
LABEL   Description="Debian with Apache and PHP + opcache with eQual dependencies" \
                Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
                License="Apache License 2.0" \
                Version="b2-equal:2.1-r1"
RUN apt-get update && apt-get install -y \
        gnupg2 \
        git \
        procps \
        netcat-traditional \
        vim \
        wget
COPY ./mpm_prefork.conf /etc/apache2/mods-available/
COPY ./000-default.conf /etc/apache2/sites-available/

# install sqlsrv drivers
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft-2025.asc \
        | gpg --dearmor \
        | tee /usr/share/keyrings/microsoft.gpg > /dev/null
RUN echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] \
https://packages.microsoft.com/debian/13/prod trixie main" \
> /etc/apt/sources.list.d/microsoft-prod.list

RUN apt-get update

RUN ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql17 \
        unixodbc \
        unixodbc-dev \
        libgssapi-krb5-2
RUN pecl install sqlsrv-5.13.3

# install required PHP extensions
RUN set -ex; \
                \
                savedAptMark="$(apt-mark showmanual)"; \
                \
                apt-get update; \
                apt-get install -y --no-install-recommends \
                                libfreetype6-dev \
                                libjpeg62-turbo-dev \
                                libxml2-dev \
                                libjpeg-dev \
                                libpng-dev \
                                libwebp-dev \
                                libzip-dev \
                                libicu-dev \
                                libpq-dev \
                                zip \
                                unzip \
                ; \
                \
                docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
                docker-php-ext-configure intl; \
                docker-php-ext-install -j"$(nproc)" \
                        gd \
                        zip \
                        intl \
                        ftp \
                        mysqli \
                        pgsql; \
                docker-php-ext-enable sqlsrv; \
                \
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
                rm -rf /var/cache/apt/archives /var/lib/apt/lists/*

# set recommended PHP.ini settings
RUN { \
                echo 'opcache.memory_consumption=256'; \
                echo 'opcache.interned_strings_buffer=64'; \
                echo 'opcache.max_accelerated_files=8000'; \
                echo 'opcache.revalidate_freq=60'; \
                echo 'opcache.enable_cli=1'; \
        } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# dedicated directory for dynamically installed binaries
# (allows to keep binaries installed by eQual across container restarts)
RUN mkdir -p /opt/bin
ENV PATH="/opt/bin:${PATH}"

# enable required Apache mods
RUN a2enmod rewrite expires

VOLUME /var/www/html

CMD ["apache2-foreground"]
