#!/bin/bash

if [ -z "$1" ]
then
    SCRIPT=`realpath $0`
    CURRENT=`dirname $SCRIPT`
else
    DOMAIN_NAME="$1"
    CURRENT="/home/$DOMAIN_NAME/status/maintenance"
fi

cd $CURRENT    
    
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
