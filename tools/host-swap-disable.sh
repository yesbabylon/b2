#!/bin/bash

set -euo pipefail

SWAP_FILE="/swapfile"
FSTAB_FILE="/etc/fstab"

if [ "$#" -ne 0 ]; then
    echo "Usage: sudo $0"
    exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
    echo "Error: this script must be run as root."
    exit 1
fi

for COMMAND in swapoff swapon awk grep cp date mktemp cat rm; do
    if ! command -v "$COMMAND" >/dev/null 2>&1; then
        echo "Error: required command not found: $COMMAND"
        exit 1
    fi
done

if [ ! -f "$FSTAB_FILE" ]; then
    echo "Error: file not found: $FSTAB_FILE"
    exit 1
fi

WAS_ACTIVE=0
FSTAB_BACKUP=""

restore_on_error() {
    if [ -n "$FSTAB_BACKUP" ] && [ -f "$FSTAB_BACKUP" ]; then
        cp -p "$FSTAB_BACKUP" "$FSTAB_FILE" >/dev/null 2>&1 || true
    fi

    if [ "$WAS_ACTIVE" -eq 1 ]; then
        swapon "$SWAP_FILE" >/dev/null 2>&1 || true
    fi
}

trap restore_on_error ERR

if swapon --noheadings --raw --show=NAME | grep -Fxq -- "$SWAP_FILE"; then
    WAS_ACTIVE=1
    swapoff "$SWAP_FILE"
    echo "Swap disabled: $SWAP_FILE"
else
    echo "$SWAP_FILE is not active."
fi

if awk -v swap_file="$SWAP_FILE" '
    $1 == swap_file && $3 == "swap" { found = 1 }
    END { exit !found }
' "$FSTAB_FILE"; then
    TEMP_FILE=$(mktemp "${FSTAB_FILE}.tmp.XXXXXX")
    trap 'rm -f "$TEMP_FILE"' EXIT

    awk -v swap_file="$SWAP_FILE" '
        !($1 == swap_file && $3 == "swap") { print }
    ' "$FSTAB_FILE" > "$TEMP_FILE"

    TIMESTAMP=$(date '+%Y%m%d-%H%M%S')
    FSTAB_BACKUP="${FSTAB_FILE}.bak-${TIMESTAMP}"
    COUNTER=0

    while [ -e "$FSTAB_BACKUP" ]; do
        COUNTER=$((COUNTER + 1))
        FSTAB_BACKUP="${FSTAB_FILE}.bak-${TIMESTAMP}-${COUNTER}"
    done

    cp -p "$FSTAB_FILE" "$FSTAB_BACKUP"
    cat "$TEMP_FILE" > "$FSTAB_FILE"

    echo "Persistent swap entry removed from $FSTAB_FILE"
    echo "Backup created: $FSTAB_BACKUP"
else
    echo "No persistent swap entry found in $FSTAB_FILE"
fi

trap - ERR

if [ -f "$SWAP_FILE" ]; then
    echo "Swap file retained: $SWAP_FILE"
fi
