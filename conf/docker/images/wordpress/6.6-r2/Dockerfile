
FROM php:8.3.36-apache-bookworm

LABEL Description="WordPress 6.6 runtime with Apache, PHP 8.3 and required dependencies" \
      Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
      License="Apache License 2.0" \
      Version="b2-wordpress:6.6-r2"

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    procps \
    netcat-traditional \
    vim \
    ghostscript \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN set -eux; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libavif-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg-dev \
        libmagickwand-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
    ; \
    \
    docker-php-ext-configure gd \
        --with-avif \
        --with-freetype \
        --with-jpeg \
        --with-webp; \
    \
    docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        zip; \
    \
    pecl install imagick-3.8.1; \
    docker-php-ext-enable imagick; \
    rm -rf /tmp/pear; \
    \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    \
    extDir="$(php -r 'echo ini_get("extension_dir");')"; \
    ldd "$extDir"/*.so \
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); printf "*%s\n", so }' \
        | sort -u \
        | xargs -r dpkg-query --search \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
    \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*; \
    \
    ! { ldd "$extDir"/*.so | grep 'not found'; }

# OPcache configuration
RUN set -eux; \
    { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=2'; \
    } > "$PHP_INI_DIR/conf.d/opcache-recommended.ini"

# Enable required Apache modules
RUN a2enmod rewrite expires

# Custom Apache MPM configuration
COPY ./mpm_prefork.conf /etc/apache2/mods-available/

# WordPress files provided externally
VOLUME /var/www/html

CMD ["apache2-foreground"]
