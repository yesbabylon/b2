#!/bin/bash

# shellcheck disable=SC2155
export script_dir=$(pwd)

# Default values
INSTANCE_NUMBER=""
WITH_WP=false
WITH_SB=false
export PMA_HOSTNAME="phpmyadmin"

flags_help() {
    echo "Usage: $0 [options]"
    echo "Options:"
    echo "  --instance_number, -n <number>  Instance number"
    echo "  --with_wp, -w                   Install WordPress"
    echo "  --with_sb, -s                   Install Symbiose"
    exit 1
}

# Parse options
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --instance_number|-n ) INSTANCE_NUMBER="$2"; shift ;;
        --with_wp|-w ) WITH_WP=true ;;
        --with_sb|-s ) WITH_SB=true ;;
        --help|-h ) flags_help ;;
        * ) flags_help ; exit 1 ;;
    esac
    shift
done

# Set container name based on instance number
if [ -z "$INSTANCE_NUMBER" ]; then
    export CONTAINER_NAME="equalpress"
else
    export CONTAINER_NAME="equalpress$INSTANCE_NUMBER"
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
##
#        # directories for backup and replication
#        mkdir /home/"$USERNAME"/import
#        mkdir /home/"$USERNAME"/export
##
#        # directories for dealing with status
#        cp -r /home/docker/accounts/status /home/"$USERNAME"/status
##
        # set the home directory of the new user (FTP access)
        mkdir -p /home/"$USERNAME"/www

#        sudo usermod -d /home/"$USERNAME"/www "$USERNAME"
##
#        # create a directory for maintenance switch
#        mkdir /srv/docker/nginx/html/"$USERNAME"
##
#        # add write permission to group over the www directory of the user
#        chmod g+w -R /home/"$USERNAME"/www
##
#        # restart SFTP service (to enable ftp login at user home)
#        sudo systemctl restart vsftpd
##
#        # add account to docker group
#        sudo usermod -a -G docker "$USERNAME"
##
#        # define ssh-login as shell for user account
#        sudo chsh -s /usr/local/bin/ssh-login "$USERNAME"
##
#        # copy docker-compose files
##       cp -r /home/docker/templates/"$TEMPLATE"/. /home/"$USERNAME"/
##
#        # shellcheck disable=SC2129
#        echo "DOMAIN_NAME=$USERNAME" >> /home/"$USERNAME"/.env
#        echo "DOMAIN_CONTACT=info@$USERNAME" >> /home/"$USERNAME"/.env
#        echo "TEMPLATE=$TEMPLATE" >> /home/"$USERNAME"/.env
##
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
        export DB_HOSTNAME="db_$HASH_VALUE"

        # Rename PHPMYADMIN_SERVICE_NAME with the hash value
        export PMA_HOSTNAME="${PMA_HOSTNAME}_$HASH_VALUE"

        # Get the number of directories in /home
        # shellcheck disable=SC2010
        number_of_directories=$(ls -l /home | grep -c ^d)

        # Define DB_PORT with the number of directories in /home
        # shellcheck disable=SC2004
        export DB_PORT=$(( 3306 - 1 + $number_of_directories ))

        # Define PHPMYADMIN_PORT with the number of directories in /home
        # shellcheck disable=SC2004
        export PHPMYADMIN_PORT=$(( 8080 - 1 + $number_of_directories ))

        # Define EQ_PORT with the number of directories in /home
        # shellcheck disable=SC2004
        export EQ_PORT=$(( 80 - 1 + $number_of_directories ))


        bash "$script_dir"/equal.setup.bash

        if [ "$WITH_SB" = true ]; then
            bash "$script_dir"/symbiose.setup.bash
        fi

        if [ "$WITH_WP" = true ]; then
            bash "$script_dir"/equalpress.setup.bash
        fi

        print_color "magenta" "Script setup completed successfully!"
    fi
else
    print_color "red" ".env file is missing"
fi
