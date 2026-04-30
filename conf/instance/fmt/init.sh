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

if [[ -z "${INSTANCE_SUBTYPE:-}" ]]; then
    printf "Missing INSTANCE_SUBTYPE environment variable.\n" >&2
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

cd "$HOME_DIR"

docker compose build
docker compose up -d
sleep 15

printf "Docker images built and containers started\n"

##################
### INIT eQual ###
##################

docker exec "$USERNAME" bash -c "
apt-get update
apt-get install -y wget
git config --global credential.helper 'cache --timeout=450'
"

docker exec "$USERNAME" bash -c "
yes | git clone -b dev-2.0 https://github.com/equalframework/equal.git .
"

docker cp ./config.json "$USERNAME":/var/www/html/config/config.json

docker exec "$USERNAME" bash -c "
./equal.run --do=init_db
./equal.run --do=init_package --package=core --import=true
"

# Modify default root and user login to use domain name in mail
docker exec "$USERNAME" bash -c "
./equal.run --do=model_update --entity='core\\User' --id=1 --fields='{\"login\":\"root@$USERNAME\"}'
"

# Update root password with the one provided
docker exec "$USERNAME" bash -c "
./equal.run --do=user_pass-update --user_id=1 --password=$PASSWORD --confirm=$PASSWORD
"

##################
###  INIT FMT  ###
##################

docker exec "$USERNAME" bash -c "
mv packages packages-old
yes | git clone https://github.com/fmt-saas/fmt.git packages
cp -r packages-old/core packages
rm -R packages-old
"

if [ "$INSTANCE_SUBTYPE" == 'agency' ]; then
    docker exec "$USERNAME" bash -c "
    ./equal.run --do=fmt_init_instance_agency --instance_uuid=$INSTANCE_UUID --global_access_token=$GLOBAL_ACCESS_TOKEN --global_instance_url=$GLOBAL_URL
    "
elif [ "$INSTANCE_SUBTYPE" == 'global' ]; then
    docker exec "$USERNAME" bash -c "
    ./equal.run --do=fmt_init_instance_global
    "
fi

touch "$INITIALIZED_FILE"
printf "eQual initialized.\n"

exit 0
