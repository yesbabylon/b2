#!/bin/bash

# #memo - This script must be run with root privileges

# Define constants for using some colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Store current directory path
INSTALL_DIR=$(pwd)


############
### Base ###
############

# Make sure aptitude cache is up-to-date
apt-get update

# Set timezone to UTC (for sync with containers having UTC as default TZ)
timedatectl set-timezone UTC

# Allow using domains as user names
mv /etc/adduser.conf /etc/adduser.conf.orig
cp "$INSTALL_DIR"/conf/etc/adduser.conf /etc/adduser.conf

# Install Apache utilities (htpasswd), vnstat (bandwidth monitoring), PHP cli (for scripts), FTP service
apt-get install -y apache2-utils vnstat php-cli vsftpd

# Custom FTP config
mv /etc/vsftpd.conf /etc/vsftpd.conf.orig
cp "$INSTALL_DIR"/conf/etc/vsftpd.conf /etc/vsftpd.conf

# Restart FTP service
systemctl restart vsftpd

# Add logrotate directive for nginx
cp "$INSTALL_DIR"/conf/etc/logrotate.d/nginx /etc/logrotate.d/nginx


######################
### Install Docker ###
######################
apt-get install -y apt-transport-https ca-certificates curl software-properties-common

# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o docker.gpg
gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg docker.gpg
rm docker.gpg

# Add Docker repository
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" > /etc/apt/sources.list.d/docker.list

# Update package list and install Docker
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io

# Start docker
systemctl start docker

# Make sure docker starts on boot
systemctl enable docker

# Prepare directory structure
cp -r "$INSTALL_DIR"/docker /home/docker
cp "$INSTALL_DIR"/conf/ssh-login /usr/local/bin/ssh-login
chmod +x /usr/local/bin/ssh-login

mkdir /srv/docker/nginx/htpasswd
mkdir /var/log/nginx

sh -c "echo '/usr/local/bin/ssh-login' >> /etc/shells"

# Create proxy network
docker network create proxynet

# Create portainer volume
docker volume create portainer_data

# Install OVH real time monitoring
# wget -qO - https://last-public-ovh-infra-yak.snap.mirrors.ovh.net/yak/archives/apply.sh | OVH_PUPPET_MANIFEST=distribyak/catalog/master/puppet/manifests/common/rtmv2.pp bash

# Build docked-nginx image
# shellcheck disable=SC2164
cd /home/docker/images/docked-nginx/
./build.sh

# Start reverse proxy and let's encrypt companion
docker compose -f /home/docker/nginx-proxy/docker-compose.yml up -d

# wait for the services to be fully started (to prevent following files to be overwritten)
sleep 30

# make sure a default maintenance page is available
cp /home/docker/images/docked-nginx/maintenance.html /srv/docker/nginx/html

# add custom nginx conf in the newly created dir env
cp "$INSTALL_DIR"/conf/nginx.conf /srv/docker/nginx/conf.d/custom.conf
# (#memo - in latest deployments, file was not created automatically by letsencrypt-companion)
mkdir -p /usr/share/nginx/html
mkdir -p /srv/docker/nginx/vhost.d
cp "$INSTALL_DIR"/conf/vhost.d/default /srv/docker/nginx/vhost.d/default

# force nginx to load new config
docker exec nginx-proxy nginx -s reload


###################
### Install F2B ###
###################

# Install F2B service (#memo - we need to do this after nginx init since F2B relies on nginx log folder)
apt-get -y install fail2ban
cp "$INSTALL_DIR"/conf/etc/fail2ban/jail.local /etc/fail2ban/jail.local
cp "$INSTALL_DIR"/conf/etc/fail2ban/action.d/* /etc/fail2ban/action.d/
cp "$INSTALL_DIR"/conf/etc/fail2ban/filter.d/* /etc/fail2ban/filter.d/
touch /etc/fail2ban/emptylog

# Make sure fail2ban starts on boot
systemctl enable fail2ban

# Restart fail2ban service
systemctl restart fail2ban


########################
### Install listener ###
########################

# Add a symbolic link for the eQual instance listener service
ln -s /root/b2/b2-listener.service /etc/systemd/system/b2-listener.service

# Reload daemon to update after symlink added
systemctl daemon-reload

# Enable the listener service
systemctl enable b2-listener.service

# Start the listener service
systemctl start b2-listener.service

# Add a symbolic link for portainer
ln -s /root/b2/docker/portainer.service /etc/systemd/system/portainer.service

# Reload daemon to update after symlink added
systemctl daemon-reload


#########################
### Install portainer ###
#########################

# Enable the portainer service
systemctl enable portainer.service

# Start the portainer service
systemctl start portainer.service

# Alert portainer running
echo -e "${RED}Portainer${NC} is running and listening on ${GREEN}http://$(hostname -I | cut -d' ' -f1):9000${NC}\n"


################
### Finished ###
################

echo "Setup is now finished."
echo "Make sure to setup services accordingly to the desired configuration."
echo -e "Then run ${GREEN}docker compose up -d${NC}"
