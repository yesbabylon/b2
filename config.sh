#!/bin/bash

# Define the .env file
ENV_FILE=".env"

# Function to prompt for a value and save it to the .env file
ask_and_save() {
    local var_name=$1
    local prompt_text=$2
    read -p "$prompt_text" value
    echo "$var_name=$value" >> "$ENV_FILE"
}

# Remove the old .env file if it exists
if [ -f "$ENV_FILE" ]; then
    rm "$ENV_FILE"
fi

echo "Creating .env file..."

touch "$ENV_FILE"

# Prompt the user for values
ask_and_save "BACKUP_HOST" "Enter the backup server IP address: "
ask_and_save "PUBLIC_IP" "Enter the public IP address: "
ask_and_save "GPG_PASSPHRASE" "Enter the GPG passphrase: "
ask_and_save "ROOT_PASSWORD" "Enter the root password: "

# Display the generated file
echo "\n.env file created successfully:"
cat "$ENV_FILE"
