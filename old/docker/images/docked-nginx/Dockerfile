FROM jwilder/nginx-proxy
LABEL   Description="nginx proxy with custom template and additional editor (vi)" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="1.0"


USER root
RUN apt-get update && apt-get install -y \
    vim 
COPY nginx.tmpl /app/
ADD maintenance.html /usr/share/nginx/html/
