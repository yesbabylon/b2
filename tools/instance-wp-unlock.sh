#!/bin/bash

set -euo pipefail

usage() {
    echo "Usage: sudo $0 USERNAME"
}

if [ "$#" -ne 1 ]; then
    usage
    exit 1
fi

if [ "$EUID" -ne 0 ]; then
    echo "Error: this script must be run as root." >&2
    exit 1
fi

USERNAME="$1"

if ! [[ "$USERNAME" =~ ^[a-zA-Z0-9][a-zA-Z0-9._-]*$ ]]; then
    echo "Error: invalid username '$USERNAME'." >&2
    exit 1
fi

if ! id "$USERNAME" >/dev/null 2>&1; then
    echo "Error: user '$USERNAME' does not exist." >&2
    exit 1
fi

WP_PATH="/home/$USERNAME/www"

if [ ! -d "$WP_PATH" ]; then
    echo "Error: WordPress directory $WP_PATH not found." >&2
    exit 1
fi

if [ ! -d "$WP_PATH/wp-content" ]; then
    echo "Error: WordPress content directory $WP_PATH/wp-content not found." >&2
    exit 1
fi

if [ ! -f "$WP_PATH/wp-config.php" ]; then
    echo "Error: WordPress configuration file $WP_PATH/wp-config.php not found." >&2
    exit 1
fi

cd -- "$WP_PATH"

# Owner: instance user; group: www-data (GID 33 in docker container).
chown -R "$USERNAME:33" .

# Default permissions.
find . -type d -exec chmod 2755 {} +
find . -type f -exec chmod 644 {} +

# Write permissions for wp-content.
find wp-content -type d -exec chmod 2775 {} +
find wp-content -type f -exec chmod 664 {} +

# Protect the WordPress configuration.
chmod 640 wp-config.php

echo "WordPress permissions updated successfully in $WP_PATH."
