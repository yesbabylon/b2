#!/bin/bash
# This script has to be run after general user initialisation: init.sh

# Create a odoo user (to link host with VM), no home, no login, no prompt
adduser --no-create-home --disabled-login --gecos "" odoo

# add new user to odoo group
adduser $USERNAME odoo

# Build docked-odoo image
chmod +x /home/docker/images/docked-odoo/build.sh
cd /home/docker/images/docked-odoo/
./build.sh


# assign ownership to user and www-data (group)
chown odoo:odoo /home/$USERNAME/www


# Build docked-odoo image
cd /home/docker/images/docked-odoo/
/home/docker/images/docked-odoo/build.sh

cd /home/$USERNAME
mkdir /home/$USERNAME/odoo

echo "retrieving addons from github repositories"
git config --global credential.helper cache
# add enterprise addons
# git clone https://github.com/odoo/enterprise --depth 1 --branch 12.0 --single-branch ./odoo/enterprise
git clone https://github.com/ARTECOM/odoo-v12.git ./odoo/enterprise
# add some external tools (previously acquired)
git clone https://github.com/ARTECOM/odoo-external.git ./odoo/external
# use a repository with internal toolkit
git clone https://github.com/ARTECOM/odoo-artecom.git ./odoo/internal
# create an empty directory for addons specific to the current project
mkdir ./odoo/custom

chown odoo:odoo -R /home/$USERNAME/odoo
    