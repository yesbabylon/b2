#!/bin/bash

# WARNING: This script is intended to be executed by `./create.bash`

#####################
### HANDLE DOCKER ###
#####################

# Add docker-compose.yml file
cp /root/b2/scripts/instance/create/docker-compose.yml /home/"$USERNAME"/docker-compose.yml

# Add config needed by docker-compose.yml
mkdir /home/"$USERNAME"/conf
cp /root/b2/scripts/instance/create/php.ini /home/"$USERNAME"/conf/php.ini
cp /root/b2/scripts/instance/create/mysql.cnf /home/"$USERNAME"/conf/mysql.cnf

# Move to newly created docker-compose.yml
cd /home/"$USERNAME"

docker compose build
docker compose up -d
sleep 15

printf "Docker images built and containers started\n"


##########################
### INIT eQual PROJECT ###
##########################

docker exec "$USERNAME" bash -c "
apt-get update
apt-get install -y wget
git config --global credential.helper 'cache --timeout=450'
"

docker exec "$USERNAME" bash -c "
yes | git clone -b dev-2.0 https://github.com/equalframework/equal.git .
"

docker exec "$USERNAME" bash -c "
./equal.run --do=config_generate --dbms=MYSQL --db_host=$DB_HOSTNAME --db_port=3306 --db_name=equal --db_username=$APP_USERNAME --db_password=$APP_PASSWORD
"
sleep 5

docker exec "$USERNAME" bash -c "
./equal.run --do=init_db
./equal.run --do=init_package --package=core --import=true
"
sleep 15

# Check if APP_USERNAME is equal to root if yes, change the password of the root user, else create the user
if [ "$APP_USERNAME" = "root" ]; then
    docker exec "$USERNAME" bash -c "
    ./equal.run --do=user_pass-update --user_id=1 --password=$APP_PASSWORD --confirm=$APP_PASSWORD
    "
else
    docker exec "$USERNAME" bash -c "
    ./equal.run --do=user_create --login=$APP_USERNAME@equal.local --password=$APP_PASSWORD
    ./equal.run --do=model_update --entity=core\\User --ids=[3] --fields="{'validated':1, 'status': 'instance'}" --force=true
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=create
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=read
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=update
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=delete
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=manage
    "
fi

printf "Testing the instance..."
wget -qO- http://0.0.0.0:"$EQ_PORT"/welcome | grep -q "Documentation"

if [ $? -eq 0 ]; then
  printf "eQual project initialized.\n"
  exit 0
else
  printf "Error: eQual project not correctly initialized.\n"
  exit 1
fi
