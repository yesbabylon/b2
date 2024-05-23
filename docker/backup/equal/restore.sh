#!/bin/bash

# enter import folder
cd /home/$DOMAIN_NAME/import

if [ -f backup.tar ]
then
    tar -xf backup.tar
    gzip -d database.sql.gz

    # import filestore
    tar -zxf filestore.tar.gz -C /
    # import database backup (with drop table statements)
    cat database.sql | docker exec -i sql.$DOMAIN_NAME /usr/bin/mysql -u root --password=qinoa qinoa
    
    # remove everything from /home/FQDN/import
    rm -rf /home/$DOMAIN_NAME/import/*
else
    echo "no backup to restore"
fi
