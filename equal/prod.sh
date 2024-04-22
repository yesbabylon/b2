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

# Define the key-value pairs
declare -A env_values=(
    ["KEY1"]="VALUE1"
    ["KEY2"]="VALUE2"
    ["KEY3"]="VALUE3"
)

# Check if the .env file exists
if [ -f .env ]; then
    # Loop through the key-value pairs
    for key in "${!env_values[@]}"; do
        # Check if the key exists in the .env file
        if grep -q "^$key=" .env; then
            # Escape special characters in the new value
            new_value="${env_values[$key]}"
            new_value_escaped=$(printf '%s\n' "$new_value" | sed -e 's/[\/&]/\\&/g')

            # Replace the value in the .env file
            sed -i "s/^$key=.*/$key=$new_value_escaped/" .env
            print_color "green" "Value for $key changed to $new_value"
        else
            print_color "red" "Key $key not found in .env file"
        fi
    done
else
    print_color "bgred" ".env file not found"
fi
