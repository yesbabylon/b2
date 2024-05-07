#!/bin/bash

if [ -f ../../.env ]
then
    # export vars from .env file
    set -a
    . ./../../.env

    touch /var/spool/cron/crontabs/root
    # suppress previous .tar backup achive
    (crontab -l ; echo "0 0 * * * /usr/bin/php /home/docker/backup/cleanup.php $DOMAIN_NAME")| crontab -
    # run backup task (e.g. filestore+DB)
    (crontab -l ; echo "30 0 * * * /home/docker/backup/backup.sh $DOMAIN_NAME")| crontab -
    # export newly created backup to target storage (FS or FTP)
    (crontab -l ; echo "30 1 * * * /usr/bin/php /home/docker/backup/export.php $DOMAIN_NAME")| crontab -

    service cron restart
    set +a
else
    echo ".env file is missing"
fi
