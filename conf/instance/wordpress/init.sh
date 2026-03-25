#!/bin/bash
set -euo pipefail

FORCE=0
if [[ "${1:-}" == "--force" ]]; then
    FORCE=1
fi

if [[ -f .env ]]; then
    set -a
    source .env
    set +a
fi

if [[ -z "${USERNAME:-}" ]]; then
    printf "Missing USERNAME environment variable.\n" >&2
    exit 1
fi

HOME_DIR="/home/$USERNAME"
LOCK_FILE="$HOME_DIR/.init.lock"
INITIALIZED_FILE="$HOME_DIR/.initialized"
WWW_DIR="$HOME_DIR/www"

cleanup() {
    rm -f "$LOCK_FILE"
}
trap cleanup EXIT INT TERM

if [[ -e "$LOCK_FILE" ]]; then
    printf "Initialization already in progress for %s.\n" "$USERNAME" >&2
    exit 1
fi

touch "$LOCK_FILE"

if [[ -f "$INITIALIZED_FILE" && "$FORCE" -ne 1 ]]; then
    printf "Instance already initialized. Use --force to reinitialize.\n" >&2
    exit 1
fi

if [[ -d "$WWW_DIR" ]] && find "$WWW_DIR" -mindepth 1 -print -quit | grep -q . && [[ "$FORCE" -ne 1 ]]; then
    printf "Directory ./www already contains data. Use --force to reinitialize.\n" >&2
    exit 1
fi

# build image docked-wordpress
cd /root/b2/conf/docker/images/docked-wordpresss/
chmod +x build.sh
./build.sh

cd "$HOME_DIR"

docker compose build
docker compose up -d
sleep 15

printf "Docker images built and containers started\n"

######################
### INIT Wordpress ###
######################


exit 0
