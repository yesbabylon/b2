#!/bin/bash

# WARNING: This script is intended to be executed by `/controllers/instance/create.php`

##############
### CHECKS ###
##############

# Verify that git is installed
if ! command -v git &> /dev/null; then
    printf "Git is not installed. Please install Git before running this script.\n"
    exit 1
else
    printf "Git OK\n"
fi

# Verify that docker is installed
if ! command -v docker &> /dev/null; then
    printf "Docker is not installed. Please install Docker before running this script.\n"
    exit 1
else
    printf "Docker OK\n"
fi

required_vars=(
    "USERNAME"
    "APP_USERNAME"
    "APP_PASSWORD"
    "CIPHER_KEY"
    "WP_VERSION"
    "WP_EMAIL"
    "WP_TITLE"
    "MEM_LIMIT"
)

# Check that all required vars are set
for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        printf "Error: $var is not set or empty.\n"
        exit 1
    fi
done

printf "Env vars OK\n"


####################################
### CREATE USER AND CONFIGURE IT ###
####################################

# Create a new user and set its password
adduser --force-badname --disabled-password --gecos ",,," "$USERNAME"
echo "$USERNAME:$APP_PASSWORD" | chpasswd

# Add user to docker group
usermod -a -G docker "$USERNAME"

# Create user home directory
mkdir /home/"$USERNAME"

# Create directories for backup and replication
mkdir /home/"$USERNAME"/import
mkdir /home/"$USERNAME"/export

# Set the home directory of the new user (FTP access) + add group write right
mkdir /home/"$USERNAME"/www
usermod -d /home/"$USERNAME"/www "$USERNAME"
chmod g+w -R /home/"$USERNAME"/www

# Define ssh-login as shell for user account
chsh -s /usr/local/bin/ssh-login "$USERNAME"

# Restart SFTP service (to enable FTP login at user home)
systemctl restart vsftpd

# Create a directory for maintenance switch
mkdir /srv/docker/nginx/html/"$USERNAME"

printf "User created and configured.\n"


#########################################################
### CREATE ENV VARS FOR INSTANCES PORTS AND HOSTNAMES ###
#########################################################

# Define a hash value with the first 5 characters of the md5sum of the username
HASH_VALUE=$(printf "%.5s" "$(echo "$USERNAME" | md5sum | cut -d ' ' -f 1)")

# Define DB_HOST with the hash value
export DB_HOSTNAME="db_$HASH_VALUE"

# Define PMA_HOSTNAME with the hash value
export PMA_HOSTNAME="phpmyadmin_$HASH_VALUE"

# Get the number of directories in /home (ignore docker and ubuntu)
number_of_directories=$(ls -l /home | grep ^d | grep -v -E 'docker|ubuntu' | wc -l)

# Define DB_PORT with the number of directories in /home
export DB_PORT=$(( 3306 + $number_of_directories ))

# Define PHPMYADMIN_PORT with the number of directories in /home
export PMA_PORT=$(( 8080 + $number_of_directories ))

# Define EQ_PORT with the number of directories in /home (start at 81 because 80 is used by nginx proxy
export EQ_PORT=$(( 80 + $number_of_directories ))

# Define DB_BACKUP user access
export DB_BACKUP_USERNAME="backup"
export DB_BACKUP_PASSWORD=$(head /dev/urandom | tr -dc 'A-Za-z0-9' | head -c 16)

# Create .env file
env_file="/home/$USERNAME/.env"
touch "$env_file"
{
    echo "USERNAME=$USERNAME"
    echo "APP_USERNAME=$APP_USERNAME"
    echo "APP_PASSWORD=$APP_PASSWORD"
    echo "CIPHER_KEY=$CIPHER_KEY"
    echo "HTTPS_REDIRECT=$HTTPS_REDIRECT"
    echo ""
    echo "WP_VERSION=$WP_VERSION"
    echo "WP_EMAIL=$WP_EMAIL"
    echo "WP_TITLE=$WP_TITLE"
    echo ""
    echo "DB_HOSTNAME=$DB_HOSTNAME"
    echo "DB_PORT=$DB_PORT"
    echo ""
    echo "DB_BACKUP_USERNAME=$DB_BACKUP_USERNAME"
    echo "DB_BACKUP_PASSWORD=$DB_BACKUP_PASSWORD"
    echo ""
    echo "PMA_HOSTNAME=$PMA_HOSTNAME"
    echo "PMA_PORT=$PMA_PORT"
    echo ""
    echo "EQ_PORT=$EQ_PORT"
    echo ""
    echo "MEM_LIMIT=$MEM_LIMIT"
} > "$env_file"

printf "Env file created.\n"


########################################
### INIT eQual, Symbiose, eQualPress ###
########################################

printf "Init eQual\n"
bash "/root/b2/scripts/instance/create/init-equal.bash"

symbiose=${symbiose:-false}
equalpress=${equalpress:-false}

if [ "$symbiose" = "true" ]; then
    printf "Init Symbiose\n"
    bash "/root/b2/scripts/instance/create/init-symbiose.bash"
fi

if [ "$equalpress" = "true" ]; then
    printf "Init eQualPress\n"
    bash "/root/b2/scripts/instance/create/init-equalpress.bash"
fi

printf "Instance successfully created.\n"

exit 0
