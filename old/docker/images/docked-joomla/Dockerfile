FROM joomla:php7.4-apache
LABEL   Description="Latest version of Joomla with Apache UID and GUID injection" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="1.0"

ARG apache_uid
ARG apache_gid

RUN apt-get update && apt-get install -y \
    git \
    procps \
    netcat \
    vim \    
    && /usr/sbin/usermod -u $apache_uid www-data \
    && /usr/sbin/groupmod -g $apache_gid www-data
        
COPY mpm_prefork.conf /etc/apache2/mods-available/