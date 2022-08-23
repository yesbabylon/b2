#!/bin/bash
  
if [ -f .env ]
then
    # export vars from .env file
    set -a
    . .env
    docker exec -ti "$DOMAIN_NAME" /bin/bash
    set +a
else
    echo ".env file is missing"
fi
