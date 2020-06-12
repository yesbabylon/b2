#!/bin/bash

# This script must be run with root privileges
#
#

# Define constants for using some colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Stop and uninstall postfix, if present
service postfix stop
yes | apt-get remove postfix

# Make sure aptitude cache is up-to-date
yes | apt-get update 

# Set timezone to UTC (for synch with containers having UTC as default TZ)
timedatectl set-timezone UTC

# Allow using domains as user names
mv /etc/adduser.conf /etc/adduser.conf.orig
cp ./conf/etc/adduser.conf /etc/adduser.conf

# Install Apache utilities (htpasswd), vnstat (bandwidth monitoring), PHP cli (for scripts), FTP service
yes | apt-get install apache2-utils vnstat php-cli vsftpd

# Custom FTP config
mv /etc/vsftpd.conf /etc/vsftpd.conf.orig
cp ./conf/etc/vsftpd.conf /etc/vsftpd.conf

# Restart FTP service
systemctl restart vsftpd

# Install F2B service
yes | apt-get install fail2ban
cp ./conf/etc/fail2ban/jail.local /etc/fail2ban/jail.local
cp ./conf/etc/fail2ban/action.d/* /etc/fail2ban/action.d/
cp ./conf/etc/fail2ban/filter.d/* /etc/fail2ban/filter.d/

# Restart F2B service
systemctl restart fail2ban

# Add logrotate directive for nginx
cp ./conf/etc/logrotate.d/nginx /etc/logrotate.d/nginx

# Install Docker
yes | apt install apt-transport-https ca-certificates curl software-properties-common
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
yes | add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
yes | apt update
yes | apt-get install docker-ce docker-ce-cli containerd.io


# Install Docker-Compose
curl -L https://github.com/docker/compose/releases/download/1.21.2/docker-compose-$(uname -s)-$(uname -m) -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Prepare directory structure
cp -r ./docker /home/docker
cp ./conf/ssh-login /usr/local/bin/ssh-login

mkdir /srv/docker/nginx/htpasswd
mkdir /var/log/nginx

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
wget -qO - https://last-public-ovh-infra-yak.snap.mirrors.ovh.net/yak/archives/apply.sh | OVH_PUPPET_MANIFEST=distribyak/catalog/master/puppet/manifests/common/rtmv2.pp bash


# Build docked-nginx image
cd /home/docker/images/docked-nginx/
./build.sh

# Start reverse proxy and let's encrypt companion
docker-compose -f /home/docker/nginx-proxy/docker-compose.yml up -d
# wait for the services to be fully started (to prevent following files to be overwritten)
sleep 5
# make sure a default maintenance page is available
cp /home/docker/images/docked-nginx/maintenance.html /srv/docker/nginx/html
# add custom nginx conf in the newly created dir env
cp ./conf/nginx.conf /srv/docker/nginx/conf.d/custom.conf
# force nginx to load new config
docker exec nginx-proxy nginx -s reload

# Edit account parameters and then Run script for account creation
cd /home/docker/accounts
vi .env ; /home/docker/accounts/init.sh

# Start Portainer
/home/docker/console_start.sh
echo -e "${RED}Portainer${NC} is running and listening on ${GREEN}http://$(hostname -I | cut -d' ' -f1):9000${NC}\n"
echo "Setup is now finished."
echo "Make sure to setup services accordingly to the desired configuration."
echo -e "Then run ${GREEN}docker-compose up -d${NC}"