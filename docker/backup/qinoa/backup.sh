#!/bin/bash


# remove previous backup, if any
rm /home/$DOMAIN_NAME/export/backup.tar
# enter export folder
cd /home/$DOMAIN_NAME/export

# generate a SQL dump (raw text) with table drop instructions, then compress it with gzip
docker exec sql.$DOMAIN_NAME /usr/bin/mysqldump -u root --password=qinoa qinoa | gzip > database.sql.gz

# create a compressed archive with the whole content of the filestore
tar -zcf filestore.tar.gz /home/$DOMAIN_NAME/www

# merge both files into a tarball
tar -cf backup.tar *.gz

# remove temporary files
rm /home/$DOMAIN_NAME/export/*.gz
