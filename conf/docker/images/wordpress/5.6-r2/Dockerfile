# La meme image PHP est utilisee pour la compilation et pour l'image finale afin
# d'eviter tout ecart d'ABI entre les binaires reconstruits et leur environnement.
ARG PHP_IMAGE=php:7.4.33-apache-buster

# ============================================================================
# 1. Reconstruction de PHP 7.4.33 avec le backport officiel PHP2Shell
#    - GHSA-w8hw-w9fx-j79w / CVE-2026-109618
#    - GHSA-6wmf-4rv5-p3rp / CVE-2026-109619
# ============================================================================
FROM ${PHP_IMAGE} AS php-patched

# Le commit est epingle pour rendre le correctif reproductible. BUILD_JOBS peut
# etre surcharge au build pour limiter la consommation CPU et memoire.
ARG PHP_FIX_COMMIT=712e60f9edfd03f0960fc4fc3c2c375f42b90aa2
ARG BUILD_JOBS=2

# Debian 10 est en fin de vie : ses paquets ne sont plus sur les miroirs actifs.
# Les controles de date doivent etre desactives pour utiliser les depots archives.
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

# Reutilise exactement les options de compilation de l'image officielle, refuse
# toute autre version de PHP, teste puis applique le patch sans tolerance (fuzz=0).
# Les artefacts sont installes sous /out et l'archive des sources corrigees sera
# reutilisee par docker-php-ext-install a l'etape 2. Le lien symbolique curl traite
# la disposition des en-tetes propre a certaines anciennes images multi-architecture.
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

# ============================================================================
# 2. Image d'execution WordPress / Apache
# ============================================================================
FROM ${PHP_IMAGE}

LABEL Description="WordPress 5.6 runtime with Apache and patched PHP 7.4.33" \
      Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
      License="Apache License 2.0" \
      Version="b2-wordpress:5.6-r2"

# Meme configuration des depots archives que dans l'etape de compilation.
RUN set -eux; \
    printf '%s\n' \
        'deb http://archive.debian.org/debian buster main' \
        'deb http://archive.debian.org/debian-security buster/updates main' \
        > /etc/apt/sources.list; \
    echo 'Acquire::Check-Valid-Until "false";' \
        > /etc/apt/apt.conf.d/99archive

# Remplace les binaires PHP d'origine et fournit les sources corrigees aux outils
# docker-php-ext-* utilises ci-dessous.
COPY --from=php-patched /out/ /

# La signature livree par l'image de base correspond aux anciennes sources et ne
# peut plus valider l'archive regeneree apres application du patch.
RUN rm -f /usr/src/php.tar.xz.asc

# Outils d'exploitation conserves dans l'image finale. Ghostscript est requis par
# certains traitements de medias WordPress.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    procps \
    netcat-traditional \
    vim \
    ghostscript \
    && rm -rf /var/lib/apt/lists/*

# Compile les extensions attendues par WordPress contre les sources PHP corrigees.
# OPcache est recompile explicitement, comme dans equal/dev-2.0-r2, tandis
# qu'Imagick est installe via PECL. Les paquets de compilation seront supprimes ;
# leurs bibliotheques d'execution sont detectees avec ldd et conservees.
RUN set -eux; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
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
        opcache \
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

# Reglages OPcache adaptes au runtime WordPress.
RUN set -eux; \
    { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=2'; \
    } > "$PHP_INI_DIR/conf.d/opcache-recommended.ini"

# Defense en profondeur contre le chemin d'attaque confirme via l'upload de session.
RUN echo 'session.upload_progress.enabled=0' \
    > "$PHP_INI_DIR/conf.d/security.ini"

# Modules Apache requis pour les permaliens et la gestion des en-tetes d'expiration.
RUN a2enmod rewrite expires

# Configuration MPM specifique a l'hebergement B2.
COPY ./mpm_prefork.conf /etc/apache2/mods-available/

# Le code WordPress est monte dans ce repertoire et n'est pas embarque dans l'image.
WORKDIR /var/www/html

# Le build echoue si une extension attendue manque ou si Apache refuse sa configuration.
RUN php -r 'foreach (["bcmath", "exif", "gd", "imagick", "intl", "mysqli", "Zend OPcache", "pdo_mysql", "zip"] as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Missing: $ext\n"); exit(1); } }' \
    && apache2ctl -t

# Declare le point de montage persistant du code et des donnees WordPress.
VOLUME /var/www/html

# Conserve le processus Apache au premier plan pour le cycle de vie du conteneur.
CMD ["apache2-foreground"]
