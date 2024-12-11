#!/bin/bash
        
# enter import folder
cd /home/$DOMAIN_NAME/import

if [ -f backup.tar ]
then
    tar -xf backup.tar
    # gzip -d database.sql.gz

    # empty filestore
    rm -rf /home/$DOMAIN_NAME/www/filestore
    # clear sessions
    rm /home/$DOMAIN_NAME/www/sessions/*
    
    # import filestore
    tar -zxf filestore.tar.gz --strip-components=3 -C /home/$DOMAIN_NAME/www/
    # import database backup (with drop table statements)
    # cat database.sql | docker exec -i sql.$DOMAIN_NAME /usr/bin/psql -U odoo

    # docker exec -i sql.$DOMAIN_NAME /usr/bin/psql -U odoo -d postgres -c 'drop database odoo;'
    # docker exec -i sql.$DOMAIN_NAME /usr/bin/psql -U odoo -d postgres -c 'create database odoo;'

    docker exec -i sql.$DOMAIN_NAME /usr/bin/pg_restore -c -U odoo -d odoo < database.dump.gz
    
    # remove everything from /home/FQDN/import
    rm -rf /home/$DOMAIN_NAME/import/*
else
    echo "no backup to restore"
fi
