#!/bin/bash
if [ -f ./../../.env ]
then
    # export vars from .env file
    set -a
    . ./../../.env

    touch enabled    
    DIRECTORY="/srv/docker/nginx/html/$DOMAIN_NAME"

    if [ ! -d "$DIRECTORY" ]
    then
      mkdir $DIRECTORY
    fi

    touch $DIRECTORY/maintenance

    
    # stop auto export
    set +a
else
    echo ".env file is missing"
fi
