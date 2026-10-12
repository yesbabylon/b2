#!/bin/bash

set -euo pipefail

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <IPV4_ADDRESS>" >&2
    echo "Example: $0 109.135.28.87" >&2
    exit 1
fi

IP=$1

if [[ ! "$IP" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; then
    echo "Error: invalid IPv4 address: $IP" >&2
    exit 1
fi

IFS='.' read -r -a ip_parts <<< "$IP"
for part in "${ip_parts[@]}"; do
    if ((10#$part > 255)); then
        echo "Error: invalid IPv4 address: $IP" >&2
        exit 1
    fi
done

for command_name in zgrep awk; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Error: $command_name is not installed or is not available in PATH." >&2
        exit 1
    fi
done

declare -a sudo_command=()
if [ "${EUID:-$(id -u)}" -ne 0 ]; then
    if ! command -v sudo >/dev/null 2>&1; then
        echo "Error: this script must be run as root or sudo must be available." >&2
        exit 1
    fi

    sudo_command=(sudo)
fi

echo "Fail2Ban ban events for $IP"
echo "========================================"

if ban_events=$("${sudo_command[@]}" zgrep -hF "Ban $IP" /var/log/fail2ban.log* 2>&1); then
    :
else
    search_status=$?
    if [ "$search_status" -gt 1 ]; then
        printf '%s\n' "$ban_events" >&2
        exit "$search_status"
    fi
    ban_events=
fi

if [ -n "$ban_events" ]; then
    printf '%s\n' "$ban_events"
else
    echo "No ban event found in /var/log/fail2ban.log*."
fi

echo
echo "NGINX requests from $IP"
echo "========================================"

if nginx_requests=$("${sudo_command[@]}" zgrep -hF "$IP" /var/log/nginx/access.log* 2>&1); then
    :
else
    search_status=$?
    if [ "$search_status" -gt 1 ]; then
        printf '%s\n' "$nginx_requests" >&2
        exit "$search_status"
    fi
    nginx_requests=
fi

if [ -n "$nginx_requests" ]; then
    printf '%s\n' "$nginx_requests" | awk -F'"' '{
        print "Date/IP : " $1
        print "Requête : " $2
        print "Referer : " $4
        print "User-Agent : " $6
        print ""
    }'
else
    echo "No request found in /var/log/nginx/access.log*."
fi
