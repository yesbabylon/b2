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

if [[ -f prepare.php ]]; then
    php prepare.php
fi

if [[ -z "${USERNAME:-}" ]]; then
    printf "Missing USERNAME environment variable.\n" >&2
    exit 1
fi

if [[ -z "${INSTANCE_SUBTYPE:-}" ]]; then
    printf "Missing INSTANCE_SUBTYPE environment variable.\n" >&2
    exit 1
fi

if [[ -z "${PASSWORD:-}" ]]; then
    printf "Missing PASSWORD environment variable.\n" >&2
    exit 1
fi

INIT_SUCCESS=0
SYNC="${SYNC:-false}"
HOME_DIR="/home/$USERNAME"
LOCK_FILE="$HOME_DIR/.init.lock"
INITIALIZED_FILE="$HOME_DIR/.initialized"
WWW_DIR="$HOME_DIR/www"
CONFIG_FILE="$HOME_DIR/config.json"
UPDATE_FILE="$HOME_DIR/update.sh"

if [[ ! -f "$CONFIG_FILE" ]]; then
    printf "Missing config.json file: %s\n" "$CONFIG_FILE" >&2
    exit 1
fi

cleanup() {
    rm -f "$LOCK_FILE"

    if [[ "$INIT_SUCCESS" -eq 1 ]]; then
        rm -f "$CONFIG_FILE"
        rm -f "$UPDATE_FILE"
    fi
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

if [[ "$FORCE" -eq 1 ]]; then
    printf "Force mode: cleaning existing www directory.\n"

    if [[ -d "$WWW_DIR" ]]; then
        find "$WWW_DIR" -mindepth 1 -maxdepth 1 -exec rm -rf {} +
    fi

    rm -f "$INITIALIZED_FILE"
fi

cd "$HOME_DIR"

printf "Trigger docker compose up\n"
docker compose build
docker compose up -d

printf "Waiting 60 seconds for containers to be properly started\n"
sleep 60

printf "Docker images built and containers started\n"

##################
### INIT eQual ###
##################

printf "Start initializing eQual.\n"

docker exec "$USERNAME" bash -c "
apt-get update
apt-get install -y wget
git config --global credential.helper 'cache --timeout=450'
"

docker exec "$USERNAME" bash -c "
yes | git clone -b 2.0.1 https://github.com/equalframework/equal.git .
"

docker cp "$CONFIG_FILE" "$USERNAME":/var/www/html/config/config.json

docker exec "$USERNAME" bash -c "
./equal.run --do=init_fs
./equal.run --do=init_db
./equal.run --do=init_package --package=core --import=true
"

# Update root password and user with the one provided
docker exec "$USERNAME" bash -c "
./equal.run --do=user_pass-update --user_id=1 --password=$PASSWORD --confirm=$PASSWORD
./equal.run --do=user_pass-update --user_id=2 --password=$PASSWORD --confirm=$PASSWORD
"

printf "eQual initialized.\n"

##################
###  INIT FMT  ###
##################

printf "Start initializing FMT.\n"

docker cp "$UPDATE_FILE" "$USERNAME":/var/www/html/update.sh

docker exec "$USERNAME" bash -c "
mv packages packages.core
yes | git clone https://github.com/fmt-saas/fmt.git packages
cp -r packages.core/core packages
rm -R packages.core
"

if [ "$INSTANCE_SUBTYPE" == 'agency' ]; then
    if [[ "$SYNC" == 'true' || "$SYNC" == '1' ]]; then
        printf "Start initializing of agency instance with synchronization.\n"

        docker exec "$USERNAME" bash -c "
        ./equal.run --do=fmt_init_instance_agency --sync=true --level=$SYNC_LEVEL --instance_uuid=$INSTANCE_UUID --global_access_token=$GLOBAL_ACCESS_TOKEN --global_instance_url=$GLOBAL_URL
        "
    else
        printf "Start initializing of agency instance.\n"

        docker exec "$USERNAME" bash -c "
        ./equal.run --do=fmt_init_instance_agency --sync=false
        "
    fi
elif [ "$INSTANCE_SUBTYPE" == 'global' ]; then
    printf "Start initializing of global instance.\n"

    docker exec "$USERNAME" bash -c "
    ./equal.run --do=fmt_init_instance_global
    "
fi

printf "Instance initialized.\n"

docker exec "$USERNAME" bash -c "
./equal.run --do=init_app --package=fmt --app=app --force=true
"

touch "$INITIALIZED_FILE"
INIT_SUCCESS=1
printf "FMT initialized.\n"

exit 0
