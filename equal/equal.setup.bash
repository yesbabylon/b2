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

print_color "yellow" "Get docker-compose.yml file"
cp /root/b2/equal/docker-compose.yml /home/"$USERNAME"/docker-compose.yml

replace_placeholders_for_docker_compose() {
    # Replace placeholders with computed values
    for key in EQ_PORT DB_HOSTNAME DB_PORT PMA_HOSTNAME PMA_PORT; do
        value=$(eval echo \$$key)
        for file in /home/"$USERNAME"/docker-compose.yml; do
            # Replace placeholder with value
            sed -i "s/{{$key}}/$value/g" "$file"
        done
    done
}

replace_placeholders_for_docker_compose

# shellcheck disable=SC2164
cd /home/"$USERNAME"

print_color "yellow" "Building and starting the containers..."
docker-compose build
docker-compose up -d

print_color "yellow" "Waiting 15 seconds for being sure than containers are correctly initialised."
sleep 15

print_color "yellow" "Installation of wget package."
docker exec "$USERNAME" bash -c "
apt update
apt install -y wget
git config --global credential.helper 'cache --timeout=450'
"

print_color "yellow" "Clone of Equal started..."
docker exec "$USERNAME" bash -c "
yes | git clone -b dev-2.0 https://github.com/AlexisVS/equal.git .
"
print_color "cyan" "Clone of eQual framework done."

print_color "yellow" "Generation of config/config.json"
docker exec "$USERNAME" bash -c "
./equal.run --do=config_generate --dbms=MYSQL --db_host=$DB_HOSTNAME --db_port=3306 --db_name=equal --db_username=$APP_USERNAME --db_password=$APP_PASSWORD
"
sleep 5

print_color "yellow" "Init eQual Framework database and core package"
print_color "yellow" "Waiting 15 seconds for the database to be initialized..."
docker exec "$USERNAME" bash -c "
./equal.run --do=init_db
./equal.run --do=init_package --package=core --import=true
"
sleep 15

# Check if APP_USERNAME is equal to root if yes, change the password of the root user, else create the user
if [ "$APP_USERNAME" = "root" ]; then
    print_color "yellow" "Changing the password of the root user..."
    docker exec "$USERNAME" bash -c "
    ./equal.run --do=user_pass-update --user_id=1 --password=$APP_PASSWORD --confirm=$APP_PASSWORD
    "
else
    print_color "yellow" "Creating the user $APP_USERNAME and granting admins rights..."
    docker exec "$USERNAME" bash -c "
    ./equal.run --do=user_create --login=$APP_USERNAME@equal.local --password=$APP_PASSWORD
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=create
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=read
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=update
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=delete
    ./equal.run --do=user_grant --user=$APP_USERNAME@equal.local --group=admins --right=manage
    "
fi

print_color "yellow" "Testing the instance..."
wget -qO- http://0.0.0.0:"$EQ_PORT"/welcome | grep -q "Documentation"

# shellcheck disable=SC2181
if [ $? -eq 0 ]; then
  print_color "bggreen" "Instance eQual OK"
else
  print_color "bgred" "Instance eQual ERROR"
fi
