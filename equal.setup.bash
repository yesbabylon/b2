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

# print_color "yellow" "Clone of Equal started..."
# yes | git clone -b "$EQ_VERSION" https://github.com/equalframework/equal.git /home/"$USERNAME"/www
# print_color "cyan" "Clone of eQual framework done."

print_color "yellow" "Get config files from the repository..."
wget https://raw.githubusercontent.com/yesbabylon/b2/master/eQualPress_template/docker-compose.yml -O /home/"$USERNAME"/www/docker-compose.yml

replace_placeholders() {
    # Replace placeholders with computed values
    for key in DB_PORT PHPMYADMIN_PORT EQ_PORT DB_NAME DB_HOSTNAME PMA_HOSTNAME; do
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

replace_placeholders


print_color "yellow" "Building and starting the containers..."
cd /home/"$USERNAME"/www || exit
docker-compose build
docker-compose up -d


print_color "yellow" "Replacing placeholders in files..."
docker exec -ti "$USERNAME" bash -c "
apt update
apt install wget
wget https://raw.githubusercontent.com/yesbabylon/b2/master/eQualPress_template/config/config.json -O config/config.json
wget https://raw.githubusercontent.com/yesbabylon/b2/master/eQualPress_template/public/assets/env/config.json -O public/assets/env/config.json
replace_placeholders() {
    # Replace placeholders with computed values
    for key in DB_PORT PHPMYADMIN_PORT EQ_PORT DB_NAME DB_HOSTNAME PMA_HOSTNAME; do
        value=$(eval echo \$$key)
        for file in docker-compose.yml config/config.json public/assets/env/config.json; do
            # Replace placeholder with value
            sed -i "s/{$key}/$value/g" "$file"
        done
    done

    # Read .env file and replace placeholders with values
    # shellcheck disable=SC2154
    while IFS='=' read -r key value; do
        for file in docker-compose.yml config/config.json public/assets/env/config.json; do
            # Replace placeholder with value
            sed -i "s/{$key}/$value/g" "$file"
        done
    done < "$script_dir"/.env
}

replace_placeholders
"

print_color "yellow" "Waiting 10 seconds for the containers starting..."
sleep 10

# print_color "yellow" "Move config/config.json file"
# print_color "yellow" "Move public/assets/env/config.json file"
# docker exec -ti "$USERNAME" bash -c "
# sh equal.run ./equal.run --do=config_generate
# "

print_color "yellow" "Init eQual Framework database and core package"
print_color "yellow" "Waiting 15 seconds for the database to be initialized..."
docker exec -ti "$USERNAME" bash -c "
sh ./equal.run --do=init_db
sh ./equal.run --do=init_package --package=core --import=true
"
sleep 15
