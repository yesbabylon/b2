#!/bin/bash
if [ -f ./../../.env ]
then
    # export vars from .env file
    set -a
    . ./../../.env

    DIRECTORY="/srv/docker/nginx/html/$DOMAIN_NAME"

    if [ -f "$DIRECTORY/maintenance" ]
    then
      rm $DIRECTORY/maintenance
    fi

    if [ -f enabled ]
    then
      rm enabled
    fi
    
    # stop auto export
    set +a
else
    echo ".env file is missing"
fi
