#!/bin/bash

set -euo pipefail

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <JAIL> <IP_ADDRESS>" >&2
    exit 1
fi

JAIL=$1
IP_ADDRESS=$2

if [[ ! "$JAIL" =~ ^[[:alnum:]][[:alnum:]_.-]*$ ]]; then
    echo "Error: invalid jail name: $JAIL" >&2
    exit 1
fi

if [[ ! "$IP_ADDRESS" =~ ^[0-9A-Fa-f:.]+$ ]] || [[ "$IP_ADDRESS" != *.* && "$IP_ADDRESS" != *:* ]]; then
    echo "Error: invalid IP address: $IP_ADDRESS" >&2
    exit 1
fi

if ! command -v fail2ban-client >/dev/null 2>&1; then
    echo "Error: fail2ban-client is not installed or is not available in PATH." >&2
    exit 1
fi

exec fail2ban-client set "$JAIL" unbanip "$IP_ADDRESS"
