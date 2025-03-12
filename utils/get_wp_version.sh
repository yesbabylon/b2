#!/bin/bash

# Check if a username is provided
if [ -z "$1" ]; then
    echo "Usage: $0 USERNAME"
    exit 1
fi

USERNAME="$1"
WP_PATH="/home/$USERNAME/www"
VERSION_FILE="$WP_PATH/wp-includes/version.php"

# Check if the WordPress directory exists
if [ ! -d "$WP_PATH" ]; then
    echo "Error: WordPress directory $WP_PATH not found."
    exit 1
fi

# Check if the version.php file exists
if [ ! -f "$VERSION_FILE" ]; then
    echo "Error: File $VERSION_FILE not found. Ensure WordPress is installed."
    exit 1
fi

# Extract the WordPress version from version.php
WP_VERSION=$(grep "^\$wp_version" "$VERSION_FILE" | awk -F"'" '{print $2}')

# Check if a version number was found
if [ -z "$WP_VERSION" ]; then
    echo "Error: Unable to determine WordPress version."
    exit 1
fi

# Output the WordPress version
echo "$WP_VERSION"
