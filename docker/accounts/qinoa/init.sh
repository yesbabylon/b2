#!/bin/bash

# Build docked-qinoa image
chmod +x /home/docker/images/docked-qinoa/build.sh
cd /home/docker/images/docked-qinoa/
./build.sh

# assign ownership to user and www-data (group)
chown -R $USERNAME:www-data /home/$USERNAME/www    
    