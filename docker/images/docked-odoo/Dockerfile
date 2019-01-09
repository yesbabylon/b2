FROM odoo:12.0
LABEL   Description="Latest version of Odoo with odoo UID and GUID injection" \
        Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
        License="Apache License 2.0" \
        Version="1.0"

ARG odoo_uid
ARG odoo_gid

USER root
RUN apt-get update && apt-get install -y \
    git \
    procps \
    netcat \
    vim \
    && /usr/sbin/usermod -u $odoo_uid odoo \
    && /usr/sbin/groupmod -g $odoo_gid odoo
ADD ./wait-for-postgres.sh /
RUN ["chmod", "+x", "/wait-for-postgres.sh"]
USER odoo
