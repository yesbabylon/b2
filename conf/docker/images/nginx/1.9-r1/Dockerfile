FROM jwilder/nginx-proxy:1.9.0
LABEL   Description="nginx proxy with custom template and additional editor (vi)" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="1.0"
USER root
RUN apt-get update \
 && apt-get install -y --no-install-recommends vim \
 && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives
COPY nginx.tmpl /app/
ADD maintenance.html /usr/share/nginx/html/
