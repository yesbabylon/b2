FROM jwilder/nginx-proxy:1.9.0
LABEL   Description="NGINX Proxy 1.9 with a custom template, maintenance page, and Vim" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="b2-nginx:1.9-r1"
USER root
RUN apt-get update \
 && apt-get install -y --no-install-recommends vim \
 && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives
COPY nginx.tmpl /app/
ADD maintenance.html /usr/share/nginx/html/
