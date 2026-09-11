#!/bin/bash

if ! compgen -G '/home/*/docker-compose.yml' >/dev/null; then
    echo "No instance found; certificate renewal skipped."
    exit 0
fi

docker exec -d letsencrypt-companion signal_le_service &&
    timeout 20 docker logs -f --since 2s letsencrypt-companion

status=$?
[ "$status" -eq 124 ] && exit 0
exit "$status"
