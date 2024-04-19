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
cp /home/b2/equal/docker-compose.yml /home/"$USERNAME"/docker-compose.yml

replace_placeholders_for_docker_compose() {
    # Replace placeholders with computed values
    for key in DB_PORT PHPMYADMIN_PORT EQ_PORT DB_NAME DB_HOSTNAME PMA_HOSTNAME DOMAIN_NAME DOMAIN_CONTACT; do
        value=$(eval echo \$$key)
        for file in docker-compose.yml; do
            # Replace placeholder with value
            sed -i "s/{$key}/$value/g" "$file"
        done
    done

    # Read .env file and replace placeholders with values
    # shellcheck disable=SC2154
    while IFS='=' read -r key value; do
        for file in docker-compose.yml; do
            # Replace placeholder with value
            sed -i "s/{$key}/$value/g" "$file"
        done
    done < "$script_dir"/.env
}

replace_placeholders_for_docker_compose

print_color "yellow" "Building and starting the containers..."
docker-compose build
docker-compose up -d

print_color "yellow" "Waiting 15 seconds for being sure than containers are correctly initialised."
sleep 15

print_color "yellow" "Installation of wget package"
docker exec -ti $USERNAME bash -c "
apt update
apt install wget
"

print_color "yellow" "Clone of Equal started..."
docker exec -ti $USERNAME bash -c "
yes | git clone -b dev-2.0 https://github.com/AlexisVS/equal.git .
"
print_color "cyan" "Clone of eQual framework done."

print_color "yellow" "Generation of config/config.json"
docker exec -ti "$USERNAME" bash -c "
 sh ./equal.run --do=config_generate --dbms=MYSQL --db_host=$DB_HOSTNAME --db_port=3306 --db_name=$DB_NAME --db_username=$APP_USERNAME --db_password=$APP_PASSWORD --app_url=$USERNAME --store=true
"

print_color "yellow" "save public/assets/env/config.json file."
docker exec -ti "$USERNAME" bash -c 'echo "$(./equal.run --get=envinfo-temp)" > public/assets/env/config.json'

print_color "yellow" "Init eQual Framework database and core package"
print_color "yellow" "Waiting 15 seconds for the database to be initialized..."
docker exec -ti "$USERNAME" bash -c "
sh ./equal.run --do=init_db
sh ./equal.run --do=init_package --package=core --import=true
"
sleep 15
