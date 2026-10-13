#!/bin/bash

# Check if a username is provided
if [ -z "$1" ]; then
    echo "Usage: $0 USERNAME"
    exit 1
fi

USERNAME="$1"

# Check if the container exists and is running
if ! docker inspect -f '{{.State.Running}}' "$USERNAME" 2>/dev/null | grep -q '^true$'; then
    echo "Error: Docker container '$USERNAME' not found or not running." >&2
    exit 1
fi

# Retrieve the PHP version from inside the container
PHP_VERSION=$(docker exec "$USERNAME" php -r 'echo PHP_VERSION;')

if [ -z "$PHP_VERSION" ]; then
    echo "Error: Unable to determine PHP version in container '$USERNAME'." >&2
    exit 1
fi

echo "$PHP_VERSION"