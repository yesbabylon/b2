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
        "bgred") echo -e "\033[1;37;41m$text\033[0m" ;;
        "bggreen") echo -e "\033[1;37;42m$text\033[0m" ;;
        *) echo "Invalid color" >&2 ;;
    esac
}

# Need a function to process script and replace .env file values with an incrementing number with a parameters the instance number
process_instance() {
    local instance_number="$1"

    print_color "yellow" "setup of instance N° $instance_number"

    # Create .env file inside .eQualPress_template directory
    touch .env

    # fill the .env file with the instance number
    echo "USERNAME=equal.local$instance_number
PASSWORD=arbitrary_password
APP_USERNAME=root
APP_PASSWORD=test

DB_HOST=equal_db
DB_NAME=equal

EQ_VERSION=dev-2.0

WP_VERSION=latest
WP_TITLE=eQualpress$instance_number
WP_EMAIL=root@host.local" > .env

    # Launch script init.bash with the instance number as parameter by default: 'equalpress'
    bash init.bash $instance_number

    EQ_PORT=$(( 80 - 1 + $(ls -l /home | grep -c ^d) ))


    # Assert test with wget for having a code 200 else error code: instance $instance_number problem
    print_color "cyan" "Testing Wordpress instance http://equal.local$instance_number:$EQ_PORT"
    wget -qO- http://equal.local$instance_number:"$EQ_PORT" | grep -q "eQualpress$instance_number"
    if [ $? -eq 0 ]; then
        print_color "bggreen" "Instance Wordpress OK"
    else
        print_color "bgred" "Instance Wordpress ERROR"
    fi

    # Insert break line
    echo ""

    print_color "cyan" "Testing eQual instance http://equal.local$instance_number:$EQ_PORT/welcome"
    wget -qO- http://equal.local$instance_number:"$EQ_PORT"/welcome | grep -q "Documentation"
    if [ $? -eq 0 ]; then
        print_color "bggreen" "Instance eQual OK"
    else
        print_color "bgred" "Instance eQual ERROR"
    fi

    # Insert break line
    echo ""
}

print_color "magenta" "Test eQualPress multi-instance setup script"

for i in 1 2 3; do
    process_instance "$i"
done

print_color "magenta" "Test eQualPress multi-instance setup script done"

