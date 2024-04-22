#!/bin/bash

# shellcheck disable=SC2155
export script_dir=$(pwd)

# Default values
export WITH_WP=false
export WITH_SB=false

flags_help() {
    echo "Usage: $0 [options]"
    echo "Options:"
    echo "  --with_wp, -w                   Install WordPress"
    echo "  --with_sb, -s                   Install Symbiose"
    exit 1
}

# Parse options
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --with_wp|-w ) export WITH_WP=true ;;
        --with_sb|-s ) export WITH_SB=true ;;
        --help|-h ) flags_help ;;
        * ) flags_help ; exit 1 ;;
    esac
    shift
done

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
        "bgred") echo -e "\033[1;37;41m$text\033[0m" ;;
        "bggreen") echo -e "\033[1;37;42m$text\033[0m" ;;
        *) echo "Invalid color" >&2 ;;
    esac
}

print_color "magenta" "Welcome to eQualpress setup script!"

print_color "yellow" "Check if Git is installed..."
if ! command -v git &> /dev/null; then
    print_color "bgred" "Git is not installed. Please install Git before running this script."
    exit 1
else
    print_color "bggreen" "Git OK"
fi

print_color "yellow" "Check if Docker is installed..."
if ! command -v docker &> /dev/null; then
    print_color "bgred" "Docker is not installed. Please install Docker before running this script."
    exit 1
else
    print_color "bggreen" "Docker OK"
fi

print_color "yellow" "Check if head is installed..."
if ! type head &> /dev/null; then
    print_color "bgred" "head package is not installed. Please install head before running this script."
    exit 1
else
    print_color "bggreen" "head OK"
fi

print_color "yellow" "Check if .env file exists"

if [ ! -f .env ]
then
    print_color "bgred" "A file named .env is expected and should contain following vars definition:"
    print_color "bgred" "USERNAME={domain-name-as-user-name}"
    print_color "bgred" "APP_USERNAME={user-login}"
    print_color "bgred" "APP_PASSWORD={user-password}"
    print_color "bgred" "DB_HOSTNAME={Database hostname}"
    print_color "bgred" "EQ_PORT={Equal Port}"
    exit 1
fi

print_color "yellow" "Generate MD5 hash."
md5_hash=$(echo -n "$(head -c 32 /dev/urandom | xxd -p)" | md5sum | cut -d ' ' -f1)

print_color "yellow" "Replace the CIPHER_KEY value in the .env file."
sed -i "s/^CIPHER_KEY=.*/CIPHER_KEY=$md5_hash/" .env

print_color "yellow" "Load .env file..."
set -o allexport
source .env
set +o allexport

if [ ${#USERNAME} -gt 32 ]
then 
    print_color "bgred" "Error: username must be max 32 chars long"
    exit 1
fi

print_color "yellow" "Create a new user"
adduser --force-badname --disabled-password --gecos ",,," "$USERNAME"
echo "$USERNAME:$PASSWORD" | sudo chpasswd

print_color "yellow" "Create directories for backup and replication"
mkdir /home/"$USERNAME"
mkdir /home/"$USERNAME"/import
mkdir /home/"$USERNAME"/export

print_color "yellow" "directories for dealing with status"
cp -r /home/docker/accounts/status /home/"$USERNAME"/status

print_color "yellow" "Set the home directory of the new user (FTP access)"
mkdir -p /home/"$USERNAME"/www

print_color "yellow" "Copy the .env file to user directory."
cp .env /home/"$USERNAME"/www/.env

sudo usermod -d /home/"$USERNAME"/www "$USERNAME"

print_color "yellow" "Create a directory for maintenance switch"
mkdir /srv/docker/nginx/html/"$USERNAME"

print_color "yellow" "Add write permission to group over the www directory of the user"
chmod g+w -R /home/"$USERNAME"/www

print_color "yellow" "Restart SFTP service (to enable ftp login at user home)"
sudo systemctl restart vsftpd

print_color "yellow" "Add account to docker group"
sudo usermod -a -G docker "$USERNAME"

print_color "yellow" "Define ssh-login as shell for user account"
sudo chsh -s /usr/local/bin/ssh-login "$USERNAME"

print_color "yellow" "Define a hash value with the first 5 characters of the md5sum of the username"
HASH_VALUE=$(printf "%.5s" "$(echo "$USERNAME" | md5sum | cut -d ' ' -f 1)")

print_color "yellow" "Define DB_HOST with the hash value"
export DB_HOSTNAME="db_$HASH_VALUE"

print_color "yellow" "Rename PHPMYADMIN_SERVICE_NAME with the hash value"
export PMA_HOSTNAME="phpmyadmin_$HASH_VALUE"

print_color "yellow" "Get the number of directories in /home"
# shellcheck disable=SC2010
number_of_directories=$(ls -l /home | grep -c ^d)

print_color "yellow" "Define DB_PORT with the number of directories in /home"
# shellcheck disable=SC2004
export DB_PORT=$(( 3306 - 1 + $number_of_directories ))

print_color "yellow" "Define PHPMYADMIN_PORT with the number of directories in /home"
# shellcheck disable=SC2004
export PMA_PORT=$(( 8080 - 1 + $number_of_directories ))

print_color "yellow" "Define EQ_PORT with the number of directories in /home"
# shellcheck disable=SC2004
export EQ_PORT=$(( 80 - 1 + $number_of_directories ))

bash "$script_dir"/equal.setup.bash

if [ "$WITH_SB" = true ]; then
    print_color "yellow" "Installation of Symbiose."
    bash "$script_dir"/symbiose.setup.bash
    print_color "yellow" "End of Symbiose installation."
fi

if [ "$WITH_WP" = true ]; then
    print_color "yellow" "Installation of eQualPress."
    wget https://raw.githubusercontent.com/eQualPress/equalpress/main/install.sh -O /home/"$USERNAME"/install.sh
    chmod +x /home/"$USERNAME"/install.sh
    /home/"$USERNAME"/install.sh
    print_color "yellow" "End of eQualPress installation"
    rm /home/"$USERNAME"/install.sh
fi

print_color "magenta" "Script setup completed successfully!"
