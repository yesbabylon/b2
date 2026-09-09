#!/bin/bash

# Exit on error
set -e

# --- Check argument ---
if [ -z "$1" ]; then
    echo "Usage: $0 <new-hostname>"
    exit 1
fi

NEW_HOSTNAME="$1"

# --- Validate hostname (basic check) ---
if ! [[ "$NEW_HOSTNAME" =~ ^[a-zA-Z0-9.-]+$ ]]; then
    echo "Error: Invalid hostname. Use only letters, numbers, dots and hyphens."
    exit 1
fi

echo "Setting hostname to: $NEW_HOSTNAME"

# --- Set hostname ---
sudo hostnamectl set-hostname "$NEW_HOSTNAME"

# --- Update /etc/hosts ---
if grep -q "127.0.1.1" /etc/hosts; then
    sudo sed -i "s/^127.0.1.1.*/127.0.1.1\t$NEW_HOSTNAME/" /etc/hosts
else
    echo -e "127.0.1.1\t$NEW_HOSTNAME" | sudo tee -a /etc/hosts > /dev/null
fi

# --- Done ---
echo "Hostname successfully updated."
echo "You may need to restart your session (exec bash or reconnect)."

# --- Show result ---
hostname