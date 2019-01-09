#!/bin/bash

if [ -f ../../.env ]
then
    # export vars from .env file
    set -a
    . ./../../.env

    touch /var/spool/cron/crontabs/root
    (crontab -l ; echo "0 */7 * * * /usr/bin/php /home/docker/backup/ftp/import.php $DOMAIN_NAME")| crontab -    
    (crontab -l ; echo "30 */7 * * * /home/docker/backup/restore.sh $DOMAIN_NAME")| crontab -

    service cron restart
    set +a
else
    echo ".env file is missing"
fi
