#!/bin/bash


# remove previous backup, if any
rm /home/$DOMAIN_NAME/export/backup.tar

# enter export folder
cd /home/$DOMAIN_NAME/export

if [[ (-f "/home/$DOMAIN_NAME/status/maintenance/enable.sh") && (-f "/home/$DOMAIN_NAME/status/maintenance/disable.sh") ]]
then
    echo "Environement OK."
    echo "Preparing to backup database..."
else
    echo "Unable to switch to maintenance mode:"
    echo "    one of these or both files are missing"
    echo "    /home/$DOMAIN_NAME/status/maintenance/enable.sh"
    echo "    /home/$DOMAIN_NAME/status/maintenance/disable.sh"
    exit 1    
fi    

# enable maintenance mode (at nginx level)
echo "Switching to maintenance mode..."
/home/$DOMAIN_NAME/status/maintenance/enable.sh $DOMAIN_NAME

# wait a bit
echo "Waiting for pending processes to terminate..."
sleep 60

# generate a SQL dump (raw text) with table drop instructions 
echo "Dumping database..."
docker exec sql.$DOMAIN_NAME /usr/bin/mysqldump -u root --password=wordpress --single-transaction --skip-lock-tables wordpress > database.sql
echo "Database dump OK."

# disable maintenance mode (at nginx level)
echo "Switching back to production mode..."
/home/$DOMAIN_NAME/status/maintenance/disable.sh $DOMAIN_NAME

# compress DB dump to a `database.sql.gz` file
gzip database.sql

# create a compressed archive with the whole content of the filestore (for WP, everything under the `www` folder)
tar -zcf filestore.tar.gz /home/$DOMAIN_NAME/www

# merge both files (`database.sql.gz` and `filestore.tar.gz`) into a tarball
tar -cf backup.tar *.gz

# remove temporary files
rm /home/$DOMAIN_NAME/export/*.gz
