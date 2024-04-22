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

# Function to display usage message
usage() {
    print_color "red" "Usage: $0 --env-path <path_to_env_file>"
    exit 1
}

# Check if the number of arguments is correct
if [ "$#" -ne 2 ]; then
    usage
fi

# Parse command line arguments
while [ "$#" -gt 0 ]; do
    case "$1" in
        --env-path)
            shift
            env_file="$1"
            ;;
        *)
            usage
            ;;
    esac
    shift
done

# Define the key-value pairs
declare -A env_values=(
    ["KEY1"]="VALUE1"
    ["KEY2"]="VALUE2"
    ["KEY3"]="VALUE3"
)

# Check if the .env file exists
if [ -f "$env_file" ]; then
    # Loop through the key-value pairs
    for key in "${!env_values[@]}"; do
        # Check if the key exists in the .env file
        if grep -q "^$key=" "$env_file"; then
            # Escape special characters in the new value
            new_value="${env_values[$key]}"
            new_value_escaped=$(printf '%s\n' "$new_value" | sed -e 's/[\/&]/\\&/g')

            # Replace the value in the .env file
            sed -i "s/^$key=.*/$key=$new_value_escaped/" "$env_file"
            print_color "green" "Value for $key changed to $new_value"
        else
            print_color "red" "Key $key not found in $env_file"
        fi
    done
else
    print_color "bgred" "$env_file not found"
fi
