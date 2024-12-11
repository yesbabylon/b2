#!/bin/bash

# Build docked-wordpress image
chmod +x /home/docker/images/docked-wordpress/build.sh
cd /home/docker/images/docked-wordpress/
./build.sh

# assign ownership to user and www-data (group)
chown -R $USERNAME:www-data /home/$USERNAME/www
chmod g+w -R /home/$USERNAME/www

# find out a unique id for DB service and replace names in docker-compose file
id=$(echo $USERNAME | md5sum | cut -d ' ' -f 1)
db_name=$(printf "db_%.5s" $id)
sed -i "s/{db_ID}/$db_name/g" /home/$USERNAME/docker-compose.yml