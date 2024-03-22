#!/bin/bash

# Function to print colored text
print_color() {
    local color="$1"
    local text="$2"
    case "$color" in
        "black") echo -e "\033[1;30m$text\033[0m" ;;
        "red") echo -e "\033[1;31m$text\033[0m" ;;
        "green") echo -e "\033[1;32m$text\033[0m" ;;
        "yellow") echo -e "\033[1;33m$text\033[0m" ;;
        "blue") echo -e "\033[1;34m$text\033[0m" ;;
        "magenta") echo -e "\033[1;35m$text\033[0m" ;;
        "cyan") echo -e "\033[1;36m$text\033[0m" ;;
        "white") echo -e "\033[1;37m$text\033[0m" ;;
        *) echo "Invalid color" >&2 ;;
    esac
}

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
        adduser --force-badname --disabled-password --gecos ",,," "$USERNAME"
        echo "$USERNAME:$PASSWORD" | sudo chpasswd

        # directories for backup and replication
        mkdir /home/"$USERNAME"/import
        mkdir /home/"$USERNAME"/export

        # directories for dealing with status
        cp -r /home/docker/accounts/status /home/"$USERNAME"/status

        # set the home directory of the new user (FTP access)
        mkdir /home/"$USERNAME"/www
        sudo usermod -d /home/"$USERNAME"/www "$USERNAME"

        # create a directory for maintenance switch
        mkdir /srv/docker/nginx/html/"$USERNAME"

        # add write permission to group over the www directory of the user
        chmod g+w -R /home/"$USERNAME"/www

        # restart SFTP service (to enable ftp login at user home)
        sudo systemctl restart vsftpd

        # add account to docker group
        sudo usermod -a -G docker "$USERNAME"

        # define ssh-login as shell for user account
        sudo chsh -s /usr/local/bin/ssh-login "$USERNAME"

        # copy docker-compose files
        cp -r /home/docker/templates/"$TEMPLATE"/. /home/"$USERNAME"/

        # shellcheck disable=SC2129
        echo "DOMAIN_NAME=$USERNAME" >> /home/"$USERNAME"/.env
        echo "DOMAIN_CONTACT=info@$USERNAME" >> /home/"$USERNAME"/.env
        echo "TEMPLATE=$TEMPLATE" >> /home/"$USERNAME"/.env

        chmod +x /home/docker/accounts/"$TEMPLATE"/init.sh
        /home/docker/accounts/"$TEMPLATE"/init.sh

        cd /home/"$USERNAME" || exit


        print_color "magenta" "Welcome to eQualpress setup script!"
        print_color "yellow" "Check if Git is installed..."
        if ! command -v git &> /dev/null; then
            print_color "red" "Git is not installed. Please install Git before running this script."
            exit 1
        fi
        print_color "cyan" "Git OK"

        print_color "yellow" "Check if Docker is installed..."
        if ! command -v docker &> /dev/null; then
            print_color "red" "Docker is not installed. Please install Docker before running this script."
            exit 1
        fi
        print_color "cyan" "Docker OK"

        print_color "yellow" "Load .env file..."
        set -o allexport
        source .env
        set +o allexport

        print_color "yellow" "Clone of Equal started..."
        yes | git clone -b "$EQ_VERSION" https://github.com/equalframework/equal.git /home/"$USERNAME"
        print_color "cyan" "Clone of eQual framework done."

        # Compute DB_PORT with the number of directories inside /home
        DB_PORT=$(( 3306 + $(ls -l /home | grep -c ^d) ))

        print_color "yellow" "Replacing placeholders in files..."
        cp -r eQualPress_template .eQualPress_template

        # Iterate over files in the directory and its subdirectories
        find ".eQualPress_template" -type f -print0 | while IFS= read -r -d '' file; do
            # Replace placeholders in each file
            while IFS='=' read -r key value; do
                # Replace placeholder with variable value
                sed -i "s/{${key}}/${value}/g" "$file"
            done < .env
        done

        print_color "green" "Move config files"
        print_color "yellow" "Waiting 5 seconds for the config files to be moved..."
        # Move files from source to destination while preserving directory structure
        source_directory=".eQualPress_template"
        destination_directory="/home/$USERNAME"
        find "$source_directory" -type f -print0 | while IFS= read -r -d '' file; do
            # Get relative path of the file from the source directory
            relative_path="${file#$source_directory/}"

            # Move the file to the destination directory
            mv "$file" "$destination_directory/$relative_path"
        done
        sleep 5

        cd /home/"$USERNAME" || exit

        print_color "yellow" "Renaming public/index.php to public/equal.php to avoid conflicts with WordPress..."
        mv public/index.php public/equal.php

        print_color "yellow" "Building and starting the containers..."
        docker-compose build
        docker-compose up -d
        print_color "yellow" "Waiting 10 seconds for the containers starting..."
        sleep 10

        print_color "yellow" "Clone an setup of Symbiose started..."
        docker exec -ti "$DOMAIN" bash -c "
        mv packages packages-core
        yes | git clone -b dev-2.0 https://github.com/yesbabylon/symbiose.git packages
        mv packages-core/{core,demo} packages/
        rm -rf packages-core
        "

#        print_color "yellow" "Move config/config.json file"
#        print_color "yellow" "Move public/assets/env/config.json file"
        #docker exec -ti "$DOMAIN" bash -c "
        #sh equal.run ./equal.run --do=config_generate
        #"

        print_color "yellow" "Init eQual Framework database and core package"
        print_color "yellow" "Waiting 15 seconds for the database to be initialized..."
        docker exec -ti "$DOMAIN" bash -c "
        sh equal.run --do=init_db
        sh equal.run --do=init_package --package=core --import=true
        "
        sleep 15

        print_color "green" "Downloading, installing and setting up WordPress"
        docker exec -ti "$DOMAIN" bash -c "
        curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
        chmod +x wp-cli.phar
        mkdir -p /usr/local/bin
        mv wp-cli.phar /usr/local/bin/wp
        wp core download --path='public/' --locale='en_US' --version=$WP_VERSION --allow-root
        wp config create --path='public/' --dbname=$DB_NAME --dbuser=$APP_USERNAME --dbpass=$APP_PASSWORD --dbhost=$DB_HOST --allow-root
        mkdir -p public/wp-content/uploads
        wp core install --path='public/' --url=$DOMAIN --title=$WP_TITLE --admin_user=$APP_USERNAME --admin_password=$APP_PASSWORD --admin_email=$WP_EMAIL --skip-email --allow-root
        chown -R www-data:www-data .
        "

        print_color "magenta" "Script setup completed successfully!"

        # stop auto export
        set +a
    fi
else
    echo ".env file is missing"
fi