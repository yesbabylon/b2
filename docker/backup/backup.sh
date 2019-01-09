#!/bin/bash

# This script places a backup.tar file in the export directory of the account
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
        . /home/$USERNAME/.env

        if [ ! -f "/home/$USERNAME/status/backup/enabled" ]
        then
            echo -e "Backups are currently disabled for $USERNAME"
            echo -e "Run following command to enable:"
            echo -e "    touch /home/$USERNAME/status/backup/enabled"
            
            set +a
            exit 1
        fi

        CURRENT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"

        chmod +x $CURRENT_DIR/$TEMPLATE/backup.sh
        $CURRENT_DIR/$TEMPLATE/backup.sh

        set +a
    else
        echo ".env file is missing"
    fi
fi
