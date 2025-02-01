#!/bin/bash

# ============================================================
# Script Name: B2 Host Setup
# Description: This script automates the installation of all
#              required dependencies to configure and operate
#              as a B2 host.
#
# Repository: https://github.com/yesbabylon/b2
# Author: Yesbabylon
# Version: 1.0
# License: LGPL
#
# Usage:
#   - Run the script as a superuser or with sudo privileges.
#   - Ensure the system has outgoing internet connectivity.
#
# Notes:
#   - The script requires a Debian/Ubuntu based distribution.
#   - Logs will be generated in /var/log directory, if applicable.
#
# Tested with: Ubuntu 24.04
#
# ============================================================


print_help() {
    echo "Usage: $0"
    echo ""
    echo "Description:"
    echo "  This script expects a file named '.env' in the current directory."
    echo "  The file must contain the following environment variable definitions:"
    echo ""
    echo "  Variables:"
    echo "    BACKUP_HOST      - Hostname or IP address of the backup host API endpoint (required)"
    echo "    GPG_PASSPHRASE   - Passphrase for the PGP key (required)"
    echo "    PUBLIC_IP        - Fail-over / Public IPv4 address (required)"
    echo "    ROOT_PASSWORD    - Password for the root account of the host (required)"
    echo ""
    echo "Example of a .env file:"
    echo "  BACKUP_HOST=backup.local"
    echo "  GPG_PASSPHRASE=your-passphrase"
    echo "  PUBLIC_IP=192.168.1.1"
    echo "  ROOT_PASSWORD=your-root-password"
    echo ""
    echo "Note:"
    echo "  Ensure the .env file is properly formatted and accessible by the script."
    echo "  Feel free to use .env.example as template."
}


# Define constants for using some colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Store current directory path
INSTALL_DIR=$(pwd)

# Check that script is started from valid directory
if [ "$INSTALL_DIR" != "/root/b2" ]; then
    echo "Error: Script must be run from /root/b2 directory. Current directory is $INSTALL_DIR."
    exit 1
fi


#####################
### Env variables ###
#####################

# Define GPG mandatory constants
GPG_NAME="$(hostname)"
GPG_EXPIRY_DATE="0"
GPG_EMAIL="$(hostname)@b2.host"

if [ ! -f .env ]
then
    echo ".env file is missing."
    print_help
    exit 0
else
    # auto export vars from .env file
    set -a
    . ./.env
    # stop auto export
    set +a

    REQUIRED_ENV_VARS=("GPG_PASSPHRASE" "PUBLIC_IP" "ROOT_PASSWORD" "BACKUP_HOST")

    for var in "${REQUIRED_ENV_VARS[@]}"; do
        if [[ -z "${!var}" ]]; then
            print_help
            exit 0
        fi
    done
fi


########################
### Allow SSH access ###
########################

echo 'PermitRootLogin yes
PasswordAuthentication yes
ClientAliveInterval 600
ClientAliveCountMax 0' > /etc/ssh/sshd_config.d/10-settings.conf

service ssh restart


#########################
### Set root password ###
#########################

usermod --password $(echo "$ROOT_PASSWORD" | openssl passwd -1 -stdin) root


################################
### Asssign Public IP (IPFO) ###
################################

if [[ "$PUBLIC_IP" != "0.0.0.0" && "$PUBLIC_IP" != "127.0.0.1" && -n "$PUBLIC_IP" ]]; then
    echo "network:
      version: 2
      vlans:
        veth0:
          id: 0
          link: ens3
          dhcp4: no
          addresses: [$PUBLIC_IP/24]" > /etc/netplan/51-failover.yaml

    chmod 600 /etc/netplan/51-failover.yaml
    netplan generate
    netplan apply
fi

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

# Install Apache utilities (htpasswd), vnstat (bandwidth monitoring), PHP cli (for API) and FTP service
apt-get install -y apache2-utils vnstat php-cli vsftpd apt-transport-https software-properties-common

# Custom FTP config
mv /etc/vsftpd.conf /etc/vsftpd.conf.orig
cp "$INSTALL_DIR"/conf/etc/vsftpd.conf /etc/vsftpd.conf

# Restart FTP service
systemctl restart vsftpd

# Add logrotate directive for nginx
cp "$INSTALL_DIR"/conf/etc/logrotate.d/nginx /etc/logrotate.d/nginx


######################
### Create gpg key ###
######################

# Create tmp key-gen.conf with command GPG_* params
sed -e "s/%GPG_NAME%/$GPG_NAME/g" \
    -e "s/%GPG_EMAIL%/$GPG_EMAIL/g" \
    -e "s/%GPG_EXPIRY_DATE%/$GPG_EXPIRY_DATE/g" \
    -e "s/%GPG_PASSPHRASE%/$GPG_PASSPHRASE/g" \
    ./conf/key-gen.conf >> ./key-gen.conf

# Create gpg key using configuration file
gpg --batch --generate-key ./key-gen.conf

# Remove tmp key-gen.conf
rm ./key-gen.conf

# Export private and public keys to pgp files
gpg --batch --pinentry-mode=loopback --yes --passphrase "$GPG_PASSPHRASE" --output ./keyring/gpg-private-key.pgp --armor --export-secret-key "$GPG_NAME"
gpg --output ./keyring/gpg-public-key.pgp --armor --export "$GPG_NAME"


######################
### Install Docker ###
######################


curl -fsSL https://get.docker.com -o get-docker.sh
sh ./get-docker.sh


# Make sure docker starts on boot
systemctl enable docker

# Prepare directory structure
cp -r "$INSTALL_DIR"/conf/docker /home/docker
cp "$INSTALL_DIR"/conf/ssh-login /usr/local/bin/ssh-login
chmod +x /usr/local/bin/ssh-login

# Copy temporary self-signed certs (required for nginx to use correct https template)
mkdir -p /srv/docker/nginx/certs
cp "$INSTALL_DIR"/conf/default.crt /srv/docker/nginx/certs/
cp "$INSTALL_DIR"/conf/default.key /srv/docker/nginx/certs/
cp "$INSTALL_DIR"/conf/dhparam.pem /srv/docker/nginx/certs/

mkdir -p /srv/docker/nginx/htpasswd
mkdir -p /var/log/nginx

sh -c "echo '/usr/local/bin/ssh-login' >> /etc/shells"

# Create proxy network
docker network create proxynet

# Create portainer volume
docker volume create portainer_data

# Build docked-nginx image
cd /home/docker/images/docked-nginx/
chmod +x ./build.sh
./build.sh

# Start reverse proxy and let's encrypt companion
docker compose -f /home/docker/nginx-proxy/docker-compose.yml up -d

# wait for the services to be fully started (to prevent following files to be overwritten)
echo -e "${GREEN}(Waiting for services to be fully started)${NC}\n"
sleep 30

# make sure a default maintenance page is available
mkdir -p /srv/docker/nginx/html && cp /home/docker/images/docked-nginx/maintenance.html /srv/docker/nginx/html

# add custom nginx conf in the newly created dir env
mkdir -p /srv/docker/nginx/conf.d && cp "$INSTALL_DIR"/conf/nginx.conf /srv/docker/nginx/conf.d/custom.conf

# (#memo - in latest deployments, file was not created automatically by letsencrypt-companion)
mkdir -p /usr/share/nginx/html
mkdir -p /srv/docker/nginx/vhost.d
cp "$INSTALL_DIR"/conf/vhost.d/default /srv/docker/nginx/vhost.d/default

# force nginx to load new config
docker exec nginx-proxy nginx -s reload


####################
### Install cron ###
####################

apt-get install -y cron

PHP_SCRIPT="cron.php"
CRON_CMD="* * * * * cd /root/b2/src && /usr/bin/php $PHP_SCRIPT"

# Check if the cron job already exists
if ! crontab -l | grep -q "$PHP_SCRIPT"; then
    # If not, add the cron job
    (crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -
fi


###################
### Install F2B ###
###################

# Install F2B service (#memo - we need to do this after nginx init since F2B relies on nginx log folder)

# #memo - this is broken with ubuntu 24.x (@see https://github.com/fail2ban/fail2ban/issues/3487)
# apt-get -y install fail2ban

wget https://launchpad.net/ubuntu/+source/fail2ban/1.1.0-1/+build/28291332/+files/fail2ban_1.1.0-1_all.deb

# wait for dpkg to be available
while sudo fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1; do
    sleep 2
done

sudo dpkg -i fail2ban_1.1.0-1_all.deb

cp "$INSTALL_DIR"/conf/etc/fail2ban/jail.local /etc/fail2ban/jail.local
cp "$INSTALL_DIR"/conf/etc/fail2ban/action.d/* /etc/fail2ban/action.d/
cp "$INSTALL_DIR"/conf/etc/fail2ban/filter.d/* /etc/fail2ban/filter.d/
touch /etc/fail2ban/emptylog

systemctl enable fail2ban
service fail2ban start


########################
### Install listener ###
########################

# Add a symbolic link for the eQual instance listener service
ln -s /root/b2/conf/b2-listener.service /etc/systemd/system/b2-listener.service

# Reload daemon to update after symlink added
systemctl daemon-reload


#########################
### Install portainer ###
#########################

# #memo - portainer is not mandatory and should not be started automatically but only when needed

# Add a symbolic link for portainer
# ln -s /root/b2/conf/docker/portainer.service /etc/systemd/system/portainer.service

# Reload daemon to update after symlink added
# systemctl daemon-reload

# Enable the portainer service
# systemctl enable portainer.service

# Start the portainer service
# systemctl start portainer.service

# Alert portainer running
# echo -e "${RED}Portainer${NC} is running and listening on ${GREEN}http://$(hostname -I | cut -d' ' -f1):9000${NC}\n"


########################################
### Remove sensitive data from .env  ###
########################################

sed -i '/GPG_PASSPHRASE=/d' "$INSTALL_DIR"/.env
sed -i '/ROOT_PASSWORD=/d' "$INSTALL_DIR"/.env


################
### Finished ###
################

echo "Setup is now finished."
echo "Make sure to setup services accordingly to the desired configuration."
echo -e "Then run ${GREEN}docker compose up -d${NC}"
