
ARG PHP_IMAGE=php:7.4.33-apache-buster

# ============================================
# 1. PHP 7.4.33 with PHP2Shell patch
#    Fix GHSA-w8hw-w9fx-j79w (CVE-2026-109618)
#    Fix GHSA-6wmf-4rv5-p3rp (CVE-2026-109619)
# ============================================

FROM ${PHP_IMAGE} AS php-patched

ARG PHP_FIX_COMMIT=712e60f9edfd03f0960fc4fc3c2c375f42b90aa2
ARG BUILD_JOBS=2

# Debian 10 : depots archives
RUN set -eux; \
    printf '%s\n' \
      'deb http://archive.debian.org/debian buster main' \
      'deb http://archive.debian.org/debian-security buster/updates main' \
      > /etc/apt/sources.list; \
    echo 'Acquire::Check-Valid-Until "false";' \
      > /etc/apt/apt.conf.d/99archive; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      $PHPIZE_DEPS \
      patch \
      apache2-dev \
      libargon2-dev \
      libcurl4-openssl-dev \
      libonig-dev \
      libreadline-dev \
      libsodium-dev \
      libsqlite3-dev \
      libssl-dev \
      libxml2-dev \
      zlib1g-dev

# Application du patch et recompilation PHP
RUN set -eux; \
    test "$PHP_VERSION" = "7.4.33"; \
    configureOptions="$(php-config --configure-options)"; \
    docker-php-source extract; \
    cd /usr/src/php; \
    curl -fsSL \
      "https://github.com/php/php-src/commit/${PHP_FIX_COMMIT}.patch" \
      -o /tmp/php2shell.patch; \
    patch --dry-run --fuzz=0 -p1 < /tmp/php2shell.patch; \
    patch --fuzz=0 -p1 < /tmp/php2shell.patch; \
    mkdir -p /out/usr/src; \
    tar -cJf /out/usr/src/php.tar.xz \
      -C /usr/src \
      --exclude=.docker-delete-me \
      --transform 's,^php,php-7.4.33,' php; \
    export CFLAGS="$PHP_CFLAGS"; \
    export CPPFLAGS="$PHP_CPPFLAGS"; \
    export LDFLAGS="$PHP_LDFLAGS"; \
    debMultiarch="$(dpkg-architecture -qDEB_BUILD_MULTIARCH)"; \
    if [ ! -e /usr/include/curl ] \
       && [ ! -e /usr/local/include/curl ]; then \
      ln -sT "/usr/include/$debMultiarch/curl" \
        /usr/local/include/curl; \
    fi; \
    ./configure $configureOptions; \
    make -j "$BUILD_JOBS"; \
    mkdir -p /out/etc/apache2/mods-available; \
    make INSTALL_ROOT=/out \
      install-sapi \
      install-binaries \
      install-build \
      install-headers \
      install-programs \
      install-modules; \
    test -s /out/usr/local/bin/php; \
    test -s /out/usr/lib/apache2/modules/libphp7.so; \
    rm -rf /out/etc


# ============================================
# 2. Image eQual / Apache
# ============================================

FROM ${PHP_IMAGE}

LABEL Description="eQual with Apache and patched PHP 7.4.33" \
      Maintainer="Cedric Francoys <cedric@equal.run>" \
      License="Apache License 2.0" \
      Version="1.1"

# Debian 10 : depots archives
RUN set -eux; \
    printf '%s\n' \
      'deb http://archive.debian.org/debian buster main' \
      'deb http://archive.debian.org/debian-security buster/updates main' \
      > /etc/apt/sources.list; \
    echo 'Acquire::Check-Valid-Until "false";' \
      > /etc/apt/apt.conf.d/99archive

# Installation de PHP corrige et de ses sources
COPY --from=php-patched /out/ /

# Suppression de la signature des anciennes sources
RUN rm -f /usr/src/php.tar.xz.asc

# Dependances systeme et extensions PHP
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      git \
      procps \
      netcat \
      vim \
      wget \
      libfreetype6-dev \
      libjpeg-dev \
      libpng-dev \
      libwebp-dev \
      libtidy-dev \
      libzip-dev \
      libicu-dev \
      zip; \
    docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg \
      --with-webp; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j 2 \
      gd \
      mysqli \
      opcache \
      zip \
      tidy \
      intl; \
    rm -rf /var/lib/apt/lists/*

# Configuration Apache
COPY ./mpm_prefork.conf /etc/apache2/mods-available/
COPY ./000-default.conf /etc/apache2/sites-available/

RUN a2enmod rewrite expires

# Configuration PHP
RUN { \
      echo 'opcache.memory_consumption=256'; \
      echo 'opcache.interned_strings_buffer=64'; \
      echo 'opcache.max_accelerated_files=8000'; \
      echo 'opcache.revalidate_freq=60'; \
      echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Defense en profondeur
RUN echo 'session.upload_progress.enabled=0' \
    > /usr/local/etc/php/conf.d/security.ini

# Installation eQual
WORKDIR /var/www/html

# Verification des extensions
RUN php -r 'foreach (["gd", "mysqli", "Zend OPcache", "zip", "tidy", "intl"] as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Missing: $ext\n"); exit(1); } }' \
    && apache2ctl -t

VOLUME /var/www/html

CMD ["apache2-foreground"]
