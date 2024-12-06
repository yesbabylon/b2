#!/bin/bash
if [ -f ./../../.env ]
then
    # export vars from .env file
    set -a
    . ./../../.env
    
    touch enabled    
    FILE="/srv/docker/nginx/htpasswd/$DOMAIN_NAME"

    if [ ! -f "$FILE" ]
    then
        # create htpasswd with default credentials (i.e. admin/admin)
        htpasswd -b -c /srv/docker/nginx/htpasswd/$DOMAIN_NAME admin admin

        # restart nginx
        cd /home/docker/nginx-proxy
        docker-compose restart     
    fi

    # stop auto export
    set +a
else
    echo ".env file is missing"
fi
