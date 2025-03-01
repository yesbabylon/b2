#!/bin/bash


#todo - handle WP settings, when required
#    echo "WP_VERSION=$WP_VERSION"
#    echo "WP_EMAIL=$WP_EMAIL"
#    echo "WP_TITLE=$WP_TITLE"

# Move to folder holding docker-compose.yml
cd /home/"$USERNAME"

docker compose build
docker compose up -d
sleep 15

printf "Docker images built and containers started\n"


##################
### INIT eQual ###
##################

docker exec "$USERNAME" bash -c "
apt-get update
apt-get install -y wget
git config --global credential.helper 'cache --timeout=450'
"

docker exec "$USERNAME" bash -c "
yes | git clone -b dev-2.0 https://github.com/equalframework/equal.git .
"

docker exec "$USERNAME" bash -c "
./equal.run --do=config_generate --dbms=MYSQL --db_host=$DB_HOSTNAME --db_port=3306 --db_name=equal --db_username=root --db_password=$PASSWORD
"
sleep 5

docker exec "$USERNAME" bash -c "
./equal.run --do=init_db
./equal.run --do=init_package --package=core --import=true
"
sleep 15

# Modify default root and user login to use domain name in mail
docker exec "$USERNAME" bash -c "
./equal.run --do=model_update --entity='core\\User' --id=1 --fields='{\"login\":\"root@$USERNAME\"}'
./equal.run --do=model_update --entity='core\\User' --id=2 --fields='{\"login\":\"user@$USERNAME\"}'
"

# Update root password with the one provided
docker exec "$USERNAME" bash -c "
./equal.run --do=user_pass-update --user_id=1 --password=$PASSWORD --confirm=$APP_PASSWORD
"
# Create a new user with same default password
docker exec "$USERNAME" bash -c "
./equal.run --do=user_create --login=$APP_USERNAME@$USERNAME --password=$PASSWORD
./equal.run --do=model_update --entity='core\\User' --id=3 --fields='{\"validated\":1, \"status\": \"instance\"}' --force=true
./equal.run --do=user_grant --user=$APP_USERNAME@$USERNAME --group=admins --right=create
./equal.run --do=user_grant --user=$APP_USERNAME@$USERNAME --group=admins --right=read
./equal.run --do=user_grant --user=$APP_USERNAME@$USERNAME --group=admins --right=update
./equal.run --do=user_grant --user=$APP_USERNAME@$USERNAME --group=admins --right=delete
./equal.run --do=user_grant --user=$APP_USERNAME@$USERNAME --group=admins --right=manage
"


printf "eQual initialized.\n"

exit 0
