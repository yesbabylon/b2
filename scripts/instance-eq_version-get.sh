#!/bin/bash

# Check if a username is provided
if [ -z "$1" ]; then
    echo "Usage: $0 USERNAME"
    exit 1
fi

USERNAME="$1"
EQ_PATH="/home/$USERNAME/www"
VERSION_FILE="$EQ_PATH/VERSION"

# Check if the eQual directory exists
if [ ! -d "$EQ_PATH" ]; then
    echo "Error: eQual directory $WP_PATH not found."
    exit 1
fi

# Check if the VERSION file exists
if [ ! -f "$VERSION_FILE" ]; then
    echo "Error: File $VERSION_FILE not found. Ensure eQual is installed."
    exit 1
fi

# Extract the eQual version from VERSION
EQ_VERSION=$(head -n 1 "$VERSION_FILE")

# Check if a version number was found
if [ -z "$EQ_VERSION" ]; then
    echo "Error: Unable to determine eQual version."
    exit 1
fi

# Output the eQual version
echo "$EQ_VERSION"
