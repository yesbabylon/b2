#!/bin/bash

set -euo pipefail

SWAP_FILE="/swapfile"
FSTAB_FILE="/etc/fstab"
DEFAULT_SIZE="2G"
SIZE="${1:-$DEFAULT_SIZE}"

usage() {
    echo "Usage: sudo $0 [SIZE]"
    echo "Example: sudo $0 2G"
    echo "SIZE is used only when $SWAP_FILE does not exist yet."
}

if [ "$#" -gt 1 ] || ! [[ "$SIZE" =~ ^[1-9][0-9]*[KMGTP]$ ]]; then
    usage
    exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
    echo "Error: this script must be run as root."
    exit 1
fi

for COMMAND in fallocate chmod mkswap swapon swapoff blkid awk grep cp date tail rm; do
    if ! command -v "$COMMAND" >/dev/null 2>&1; then
        echo "Error: required command not found: $COMMAND"
        exit 1
    fi
done

if [ ! -f "$FSTAB_FILE" ]; then
    echo "Error: file not found: $FSTAB_FILE"
    exit 1
fi

is_active() {
    swapon --noheadings --raw --show=NAME | grep -Fxq -- "$SWAP_FILE"
}

has_fstab_entry() {
    awk -v swap_file="$SWAP_FILE" '
        $1 == swap_file && $3 == "swap" { found = 1 }
        END { exit !found }
    ' "$FSTAB_FILE"
}

CREATED=0
ACTIVATED=0
FSTAB_BACKUP=""

cleanup_on_error() {
    if [ -n "$FSTAB_BACKUP" ] && [ -f "$FSTAB_BACKUP" ]; then
        cp -p "$FSTAB_BACKUP" "$FSTAB_FILE" >/dev/null 2>&1 || true
    fi

    if [ "$ACTIVATED" -eq 1 ]; then
        swapoff "$SWAP_FILE" >/dev/null 2>&1 || true
    fi

    if [ "$CREATED" -eq 1 ]; then
        rm -f -- "$SWAP_FILE"
    fi
}

trap cleanup_on_error ERR

if is_active; then
    echo "$SWAP_FILE is already active."
elif [ -e "$SWAP_FILE" ]; then
    if [ ! -f "$SWAP_FILE" ]; then
        echo "Error: $SWAP_FILE exists but is not a regular file."
        exit 1
    fi

    if ! command -v blkid >/dev/null 2>&1 || [ "$(blkid -p -s TYPE -o value "$SWAP_FILE" 2>/dev/null || true)" != "swap" ]; then
        echo "Error: $SWAP_FILE exists but is not a valid swap file. It was left unchanged."
        exit 1
    fi

    chmod 600 "$SWAP_FILE"
    swapon "$SWAP_FILE"
    ACTIVATED=1
    echo "Existing swap file activated: $SWAP_FILE"
else
    fallocate -l "$SIZE" "$SWAP_FILE"
    CREATED=1
    chmod 600 "$SWAP_FILE"
    mkswap "$SWAP_FILE"
    swapon "$SWAP_FILE"
    ACTIVATED=1
    echo "Swap file created and activated: $SWAP_FILE ($SIZE)"
fi

if ! has_fstab_entry; then
    TIMESTAMP=$(date '+%Y%m%d-%H%M%S')
    FSTAB_BACKUP="${FSTAB_FILE}.bak-${TIMESTAMP}"
    COUNTER=0

    while [ -e "$FSTAB_BACKUP" ]; do
        COUNTER=$((COUNTER + 1))
        FSTAB_BACKUP="${FSTAB_FILE}.bak-${TIMESTAMP}-${COUNTER}"
    done

    cp -p "$FSTAB_FILE" "$FSTAB_BACKUP"

    if [ -s "$FSTAB_FILE" ] && [ -n "$(tail -c 1 "$FSTAB_FILE")" ]; then
        printf '\n' >> "$FSTAB_FILE"
    fi
    printf '%s none swap sw 0 0\n' "$SWAP_FILE" >> "$FSTAB_FILE"

    echo "Persistent swap entry added to $FSTAB_FILE"
    echo "Backup created: $FSTAB_BACKUP"
else
    echo "Persistent swap entry is already present in $FSTAB_FILE"
fi

trap - ERR
swapon --show
