#!/bin/bash

# This script restores an odoo instance based on a backup.tar expected in the import directory of the account
# note: /import and /export folders never contain more than one backup (max. 5GB)

if [ -z "$1" ]
then
    echo "Please provide an account name (Fully Qualified Domain Name)"
else
    USERNAME="$1"    

    if [ -f "/home/$USERNAME/.env" ]
    then
        # export vars from .env file
        set -a

        # shellcheck disable=SC1090
        . /home/"$USERNAME"/.env

        if [ ! -f "/home/$USERNAME/status/replication/enabled" ]
        then
            echo -e "Replication is currently disabled for $USERNAME"
            echo -e "Run following command to enable:"
            echo -e "    touch /home/$USERNAME/status/replication/enabled"
            exit 1
        fi

        # stop main service
        docker stop "$USERNAME"
        
        CURRENT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"

        chmod +x "$CURRENT_DIR"/"$TEMPLATE"/restore.sh
        "$CURRENT_DIR"/"$TEMPLATE"/restore.sh

        # restart main service
        cd /home/"$USERNAME" || exit
        docker-compose up -d
        
        set +a
    else
        echo ".env file is missing"
    fi
    
fi
