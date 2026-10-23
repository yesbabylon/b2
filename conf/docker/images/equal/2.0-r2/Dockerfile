FROM php:8.3.36-apache-trixie
LABEL   Description="Debian with Apache and PHP+opcache with eQual dependencies" \
		Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
		License="Apache License 2.0" \
		Version="b2-equal:2.0-r2"
RUN apt-get update && apt-get install -y \
	gnupg2 \
	git \
	procps \
	netcat-traditional \
	vim \
	wget
COPY ./000-default.conf /etc/apache2/sites-available/
# install sqlsrv drivers
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
		| gpg --dearmor \
		> /usr/share/keyrings/microsoft-prod.gpg \
	&& curl -fsSL https://packages.microsoft.com/config/debian/13/prod.list \
		> /etc/apt/sources.list.d/microsoft-prod.list \
	&& apt-get update \
	&& ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
		msodbcsql17 \
		unixodbc-dev \
		libgssapi-krb5-2 \
	&& rm -rf /var/lib/apt/lists/*
RUN pecl install sqlsrv-5.13.3
# install required PHP extensions
RUN set -ex; \
		\
		savedAptMark="$(apt-mark showmanual)"; \
		\
		apt-get update; \
		apt-get install -y --no-install-recommends \
				libfreetype6-dev \
				libxml2-dev \
				libjpeg-dev \
				libpng-dev \
				libwebp-dev \
				libzip-dev \
				libicu-dev \
				libonig-dev \
				libpq-dev \
				zip \
				unzip \
		; \
		\
        docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
        docker-php-ext-install -j"$(nproc)" \
            gd \
            intl \
            mysqli \
            pgsql \
            zip; \
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

# dedicated directory for dynamic binaries
# (allows to keep binaries installed by eQual across container restarts)
RUN mkdir -p /opt/bin
ENV PATH="/opt/bin:${PATH}"

# enable required Apache mods
RUN a2enmod rewrite expires

VOLUME /var/www/html

CMD ["apache2-foreground"]
