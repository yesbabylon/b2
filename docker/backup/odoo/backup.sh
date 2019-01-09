#!/bin/bash

# This script places a backup.tar file in the export directory of the account
# note: /import and /export folders never contain more than one backup (max. 5GB)

# remove previous backup, if any
rm /home/$DOMAIN_NAME/export/backup.tar
# enter export folder
cd /home/$DOMAIN_NAME/export

# generate a SQL dump (raw text) with table drop instructions, then compress it with gzip
# docker exec sql.$DOMAIN_NAME /usr/bin/pg_dump -c -U odoo odoo | gzip > database.sql.gz
docker exec sql.$DOMAIN_NAME /usr/bin/pg_dump -c -Fc -U odoo odoo > database.dump.gz

# create a compressed archive with the whole content of the filestore
tar -zcf filestore.tar.gz /home/$DOMAIN_NAME/www/filestore

# merge both files into a tarball
tar -cf backup.tar *.gz

# remove temporary files
rm /home/$DOMAIN_NAME/export/*.gz
