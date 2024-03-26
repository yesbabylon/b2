#!/bin/bash

export script_dir=$(pwd)

# if flag container-name is not set, set it to equalpress
if [ -z "$1" ]
then
    export INSTANCE_NUMBER=""
    export CONTAINER_NAME="equalpress"
    export PHPMYADMIN_SERVICE_NAME="phpmyadmin"
else
    export INSTANCE_NUMBER="$1"
    export CONTAINER_NAME="equalpress$1"
    export PHPMYADMIN_SERVICE_NAME="phpmyadmin$1"
fi

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
# if .env file is missing, download it
if [ ! -f .env ]
then
    print_color "yellow" "Downloading .env file..."
    wget https://raw.githubusercontent.com/yesbabylon/b2/master/.env -O .env
fi

if [ -f .env ]
then
    print_color "magenta" "Welcome to eQualpress setup script!"
    print_color "yellow" "Load .env file..."

    # load .env variables
    set -o allexport
    source .env
    set +o allexport

    if [ -z "$USERNAME" ]
    then
        echo "A file named .env is expected and should contain following vars definition:"
        echo "USERNAME={domain-name-as-user-name}"
        echo "PASSWORD={user-password}"
        echo "TEMPLATE={account-template}"
    else
        if [ ${#USERNAME} -gt 32 ]; then echo "Error: username must be max 32 chars long" ; exit 1; fi

#        # create a new user
#        adduser --force-badname --disabled-password --gecos ",,," "$USERNAME"
#        echo "$USERNAME:$PASSWORD" | sudo chpasswd
#
#        # directories for backup and replication
#        mkdir /home/"$USERNAME"/import
#        mkdir /home/"$USERNAME"/export
#
#        # directories for dealing with status
#        cp -r /home/docker/accounts/status /home/"$USERNAME"/status
#
#        # set the home directory of the new user (FTP access)
        mkdir -p /home/"$USERNAME"/www

#        sudo usermod -d /home/"$USERNAME"/www "$USERNAME"
#
#        # create a directory for maintenance switch
#        mkdir /srv/docker/nginx/html/"$USERNAME"
#
#        # add write permission to group over the www directory of the user
#        chmod g+w -R /home/"$USERNAME"/www
#
#        # restart SFTP service (to enable ftp login at user home)
#        sudo systemctl restart vsftpd
#
#        # add account to docker group
#        sudo usermod -a -G docker "$USERNAME"
#
#        # define ssh-login as shell for user account
#        sudo chsh -s /usr/local/bin/ssh-login "$USERNAME"
#
#         copy docker-compose files
#        cp -r /home/docker/templates/"$TEMPLATE"/. /home/"$USERNAME"/
#
#        # shellcheck disable=SC2129
#        echo "DOMAIN_NAME=$USERNAME" >> /home/"$USERNAME"/.env
#        echo "DOMAIN_CONTACT=info@$USERNAME" >> /home/"$USERNAME"/.env
#        echo "TEMPLATE=$TEMPLATE" >> /home/"$USERNAME"/.env
#
#        chmod +x /home/docker/accounts/"$TEMPLATE"/init.sh
#        /home/docker/accounts/"$TEMPLATE"/init.sh

        cd /home/"$USERNAME"/www || exit

        print_color "yellow" "Check if Git is installed..."
        if ! command -v git &> /dev/null; then
            print_color "red" "Git is not installed. Please install Git before running this script."
            exit 1
        else
            print_color "cyan" "Git OK"
        fi

        print_color "yellow" "Check if Docker is installed..."
        if ! command -v docker &> /dev/null; then
            print_color "red" "Docker is not installed. Please install Docker before running this script."
            exit 1
        else
            print_color "cyan" "Docker OK"
        fi

        # Define a hash value with the first 5 characters of the md5sum of the username
        HASH_VALUE=$(printf "%.5s" "$(echo "$USERNAME" | md5sum | cut -d ' ' -f 1)")

        # Define DB_HOST with the hash value
        export DB_HOST="db_$HASH_VALUE"

        # Rename PHPMYADMIN_SERVICE_NAME with the hash value
        export PHPMYADMIN_SERVICE_NAME="${PHPMYADMIN_SERVICE_NAME}_$HASH_VALUE"

        # Get the number of directories in /home
        number_of_instances=$(ls -l /home | grep -c ^d)

        # Define DB_PORT with the number of directories in /home
        export DB_PORT=$(( 3306 - 1 + $number_of_instances ))

        # Define PHPMYADMIN_PORT with the number of directories in /home
        export PHPMYADMIN_PORT=$(( 8080 - 1 + $number_of_instances ))

        # Define EQ_PORT with the number of directories in /home
        export EQ_PORT=$(( 80 - 1 + $number_of_instances ))


        bash $script_dir/equal.setup.bash
        bash $script_dir/equalpress.setup.bash

        print_color "magenta" "Script setup completed successfully!"
    fi
else
    print_color "red" ".env file is missing"
fi