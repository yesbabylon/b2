#!/bin/bash
if [ -f ./../../.env ]
then
    # export vars from .env file
    set -a
    . ./../../.env

    if [ -f enabled ]
    then
      rm enabled
    fi
    
    FILE="/srv/docker/nginx/htpasswd/$DOMAIN_NAME"

    if [ -f "$FILE" ]
    then
        # remove htpasswd file
        rm $FILE
        # restart nginx
        cd /home/docker/nginx-proxy
        docker-compose restart
    fi
    
    # stop auto export
    set +a
else
    echo ".env file is missing"
fi
