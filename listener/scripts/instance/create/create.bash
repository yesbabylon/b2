#!/bin/bash

# WARNING: This script is intended to be executed by `/listener/controllers/instance/create.php`

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
    "WITH_SB"
    "WITH_WP"
    "WP_VERSION"
    "WP_EMAIL"
    "WP_TITLE"
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
echo "$USERNAME:$PASSWORD" | chpasswd

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

# Add scripts for instance (backup, htpasswd, maintenance, replication, restore)
tar -xzvf ./scripts.tar.gz -C /home/"$USERNAME"/scripts

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

# Get the number of directories in /home
number_of_directories=$(ls -l /home | grep -c ^d)

# Define DB_PORT with the number of directories in /home
export DB_PORT=$(( 3306 - 1 + $number_of_directories ))

# Define PHPMYADMIN_PORT with the number of directories in /home
export PMA_PORT=$(( 8080 - 1 + $number_of_directories ))

# Define EQ_PORT with the number of directories in /home
export EQ_PORT=$(( 80 - 1 + $number_of_directories ))

# Create .env file
env_file="/home/$USERNAME/.env"
touch "$env_file"
{
  echo "USERNAME=$USERNAME"
  echo "APP_USERNAME=$APP_USERNAME"
  echo "APP_PASSWORD=$APP_PASSWORD"
  echo "CIPHER_KEY=$CIPHER_KEY"
  echo "HTTPS_REDIRECT=$HTTPS_REDIRECT"
  echo "WITH_SB=$WITH_SB"
  echo "WITH_WP=$WITH_WP"
  echo ""
  echo "WP_VERSION=$WP_VERSION"
  echo "WP_EMAIL=$WP_EMAIL"
  echo "WP_TITLE=$WP_TITLE"
  echo ""
  echo "DB_HOSTNAME=$DB_HOSTNAME"
  echo "DB_PORT=$DB_PORT"
  echo ""
  echo "PMA_HOSTNAME=$PMA_HOSTNAME"
  echo "PMA_PORT=$PMA_PORT"
  echo ""
  echo "EQ_PORT=$EQ_PORT"
} > "$env_file"

printf "Env file created.\n"


########################################
### INIT eQual, Symbiose, eQualPress ###
########################################

printf "Init eQual\n"
bash "./init-equal.bash"

if [ "$WITH_SB" = true ]; then
    printf "Init Symbiose\n"
    bash "./init-symbiose.bash"
fi

if [ "$WITH_WP" = true ]; then
    printf "Init eQualPress\n"
    bash "./init-equalpress.bash"
fi

printf "Instance successfully created.\n"

exit 0
