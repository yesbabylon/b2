#!/bin/bash
  
if [ -f .env ]
then
    # export vars from .env file
    set -a
    . .env
    docker exec -ti "$USERNAME" /bin/bash
    set +a
else
    echo ".env file is missing"
fi