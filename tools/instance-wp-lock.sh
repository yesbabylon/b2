#!/bin/bash

set -euo pipefail

usage() {
    echo "Usage: sudo $0 USERNAME [full]"
}

if [ "$#" -lt 1 ] || [ "$#" -gt 2 ]; then
    usage
    exit 1
fi

if [ "$EUID" -ne 0 ]; then
    echo "Error: this script must be run as root." >&2
    exit 1
fi

USERNAME="$1"
LOCK_MODE="${2:-default}"

if [ "$#" -eq 2 ] && [ "$LOCK_MODE" != "full" ]; then
    echo "Error: invalid lock mode '$LOCK_MODE'; expected 'full'." >&2
    usage
    exit 1
fi

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

if [ ! -f "$WP_PATH/wp-config.php" ]; then
    echo "Error: WordPress configuration file $WP_PATH/wp-config.php not found." >&2
    exit 1
fi

cd -- "$WP_PATH"

# Remove write permissions from all WordPress directories and files.
find . -type d -exec chmod 2555 {} +
find . -type f -exec chmod 444 {} +

# Unless a full lock was requested, keep uploads writable by the owner and www-data group (GID 33 in the Docker container).
if [ "$LOCK_MODE" != "full" ] && [ -d wp-content/uploads ]; then
    chown -R "$USERNAME:33" wp-content/uploads
    find wp-content/uploads -type d -exec chmod 2775 {} +
    find wp-content/uploads -type f -exec chmod 664 {} +
fi

# Keep the WordPress configuration readable only by its owner and group.
chmod 440 wp-config.php

echo "WordPress files locked successfully in $WP_PATH."
