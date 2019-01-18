#!/bin/bash


# Build docked-wordpress image
chmod +x /home/docker/images/docked-wordpress/build.sh
cd /home/docker/images/docked-wordpress/
./build.sh


# assign ownership to user and www-data (group)
chown -R $USERNAME:www-data /home/$USERNAME/www
chmod g+w -R /home/$USERNAME/www
