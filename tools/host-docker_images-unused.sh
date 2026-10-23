#!/bin/bash

set -o errexit
set -o nounset
set -o pipefail

if [ "$#" -ne 0 ]; then
    echo "Usage: $0" >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "Error: docker command not found." >&2
    exit 1
fi

CONTAINER_IDS=$(docker ps -aq)
USED_IMAGE_IDS=""

if [ -n "$CONTAINER_IDS" ]; then
    readarray -t CONTAINERS <<< "$CONTAINER_IDS"
    USED_IMAGE_IDS=$(docker inspect --format '{{.Image}}' "${CONTAINERS[@]}")
fi

docker image ls --no-trunc --format '{{.ID}} {{.Repository}}:{{.Tag}}' |
while read -r ID NAME; do
    if ! grep -Fqx "$ID" <<< "$USED_IMAGE_IDS"; then
        echo "$NAME ($ID)"
    fi
done
