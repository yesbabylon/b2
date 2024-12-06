#!/bin/bash

if [ -f ../../.env ]
then
    # export vars from .env file
    set -a
    . ./../../.env

    touch /var/spool/cron/crontabs/root
    (crontab -l ; echo "0 0 * * * /usr/bin/php /home/docker/backup/cleanup.php $DOMAIN_NAME")| crontab -
    (crontab -l ; echo "0 */6 * * * /home/docker/backup/backup.sh $DOMAIN_NAME")| crontab -
    (crontab -l ; echo "30 */6 * * * /usr/bin/php /home/docker/backup/export.php $DOMAIN_NAME")| crontab -

    service cron restart
    set +a
else
    echo ".env file is missing"
fi
