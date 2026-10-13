#!/bin/bash

CONTAINER="nginx-proxy"

echo "Testing Nginx configuration..."

if ! docker exec "$CONTAINER" nginx -t; then
    echo "ERROR: Nginx configuration is invalid. Reload aborted."
    exit 1
fi

echo "Configuration valid. Reloading Nginx..."

if ! docker exec "$CONTAINER" nginx -s reload; then
    echo "ERROR: Nginx reload failed."
    exit 1
fi

echo "Nginx reloaded successfully."