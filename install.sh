#!/bin/bash

# This script must be run with root privileges
#
#

# Define constants for using some colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Make sure aptitude cache is up-to-date
yes | apt-get update 

# Allow using domains as user names
mv /etc/adduser.conf /etc/adduser.conf.orig
cp ./adduser.conf /etc/adduser.conf

# Install Apache utilities (htpasswd)
yes | apt-get install apache2-utils

# Install PHP cli (for scripts)
yes | apt-get install php-cli

# Install FTP service
yes | apt-get install vsftpd

# Custom FTP config
mv /etc/vsftpd.conf /etc/vsftpd.conf.orig
cp ./vsftpd.conf /etc/vsftpd.conf

#restart FTP service
systemctl restart vsftpd

# Install Docker
yes | apt install docker.io
systemctl start docker
systemctl enable docker

# Install Docker-Compose
curl -L https://github.com/docker/compose/releases/download/1.21.2/docker-compose-$(uname -s)-$(uname -m) -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Prepare directory structure
cp -r ./docker /home/docker
cp ./ssh-login /usr/local/bin/ssh-login

mkdir /srv/docker/nginx/htpasswd

# Create a odoo user (to link host with VM), no home, no login, no prompt
adduser --no-create-home --disabled-login --gecos "" odoo


# Set scripts as executable
chmod +x /home/docker/console_start.sh
chmod +x /home/docker/accounts/init.sh
chmod +x /home/docker/images/docked-nginx/build.sh
chmod +x /usr/local/bin/ssh-login

sh -c "echo '/usr/local/bin/ssh-login' >> /etc/shells"

# Create proxy network
docker network create proxynet
docker volume create portainer_data

# Install OVH real time monitoring
wget ftp://ftp.ovh.net/made-in-ovh/rtm/install_rtm.sh -O install_rtm.sh ; /bin/bash install_rtm.sh


# Build docked-nginx image
cd /home/docker/images/docked-nginx/
./build.sh

# Start reverse proxy and let's encrypt companion
docker-compose -f /home/docker/nginx-proxy/docker-compose.yml up -d
# make sur a default maintenance page is available
cp /home/docker/images/docked-nginx/maintenance.html /srv/docker/nginx/html

# Edit account parameters and then Run script for account creation
cd /home/docker/accounts
vi .env ; /home/docker/accounts/init.sh

# Start Portainer
/home/docker/console_start.sh
echo -e "${RED}Portainer${NC} is running and listening on ${GREEN}http://$(hostname -I | cut -d' ' -f1):9000${NC}\n"
echo "Setup is now finished."
echo "Make sure to setup services accordingly to the desired configuration."
echo -e "Then run ${GREEN}docker-compose up -d${NC}"