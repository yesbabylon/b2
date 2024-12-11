#!/bin/bash

if [ -f .env ]
then
    # export vars from .env file
    set -a
    . ./.env

    if [ -z "$USERNAME" ]
    then 
        echo "A file named .env is expected and should contain following vars definition:"
        echo "USERNAME={domain-name-as-user-name}"
        echo "PASSWORD={user-password}"        
        echo "TEMPLATE={account-template}"
    else
        # todo : we should check USERNAME against the max 32 chars length
        if [ ${#USERNAME} -gt 32 ]; then echo "Error: username must be max 32 chars long" ; exit; fi

        # create a new user
        adduser --force-badname --disabled-password --gecos ",,," $USERNAME
        echo "$USERNAME:$PASSWORD" | sudo chpasswd
        
        # directories for backup and replication
        mkdir /home/$USERNAME/import
        mkdir /home/$USERNAME/export

        # directories for dealing with status
        cp -r /home/docker/accounts/status /home/$USERNAME/status
        
        # set the home directory of the new user (FTP access)
        mkdir /home/$USERNAME/www
        sudo usermod -d /home/$USERNAME/www $USERNAME
        
        # create a directory for maintenance switch
        mkdir /srv/docker/nginx/html/$USERNAME
        
        # add write permission to group over the www directory of the user        
        chmod g+w -R /home/$USERNAME/www

        # restart SFTP service (to enable ftp login at user home)
        sudo systemctl restart vsftpd

        # add account to docker group
        sudo usermod -a -G docker $USERNAME
        
        # define ssh-login as shell for user account
        sudo chsh -s /usr/local/bin/ssh-login $USERNAME

        # copy docker-compose files
        cp -r /home/docker/templates/$TEMPLATE/. /home/$USERNAME/
        
        echo "DOMAIN_NAME=$USERNAME" >> /home/$USERNAME/.env
        echo "DOMAIN_CONTACT=info@$USERNAME" >> /home/$USERNAME/.env
        echo "TEMPLATE=$TEMPLATE" >> /home/$USERNAME/.env
        
		echo "EXTERNAL_IP_ADDRESS=$(ip -4 addr show ens3 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')" >> /home/$USERNAME/.env
        chmod +x /home/docker/accounts/$TEMPLATE/init.sh
        /home/docker/accounts/$TEMPLATE/init.sh
        
        cd /home/$USERNAME
        
        # stop auto export
        set +a
    fi
else
    echo ".env file is missing"
fi
