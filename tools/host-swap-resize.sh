#!/bin/bash

set -euo pipefail

SWAP_FILE="/swapfile"
FSTAB_FILE="/etc/fstab"
SIZE="${1:-}"
SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
ENABLE_SCRIPT="$SCRIPT_DIR/host-swap-enable.sh"
DISABLE_SCRIPT="$SCRIPT_DIR/host-swap-disable.sh"

usage() {
    echo "Usage: sudo $0 <SIZE>"
    echo "Example: sudo $0 4G"
}

if [ "$#" -ne 1 ] || ! [[ "$SIZE" =~ ^[1-9][0-9]*[KMGTP]$ ]]; then
    usage
    exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
    echo "Error: this script must be run as root."
    exit 1
fi

for COMMAND in swapon swapoff blkid grep cp mv rm mktemp date; do
    if ! command -v "$COMMAND" >/dev/null 2>&1; then
        echo "Error: required command not found: $COMMAND"
        exit 1
    fi
done

for HELPER_SCRIPT in "$ENABLE_SCRIPT" "$DISABLE_SCRIPT"; do
    if [ ! -x "$HELPER_SCRIPT" ]; then
        echo "Error: helper script is missing or not executable: $HELPER_SCRIPT"
        exit 1
    fi
done

if [ ! -f "$FSTAB_FILE" ]; then
    echo "Error: file not found: $FSTAB_FILE"
    exit 1
fi

if [ ! -f "$SWAP_FILE" ]; then
    echo "Error: swap file not found: $SWAP_FILE"
    echo "Use $ENABLE_SCRIPT $SIZE to create it."
    exit 1
fi

if [ "$(blkid -p -s TYPE -o value "$SWAP_FILE" 2>/dev/null || true)" != "swap" ]; then
    echo "Error: $SWAP_FILE is not a valid swap file. It was left unchanged."
    exit 1
fi

WAS_ACTIVE=0
if swapon --noheadings --raw --show=NAME | grep -Fxq -- "$SWAP_FILE"; then
    WAS_ACTIVE=1
fi

TIMESTAMP=$(date '+%Y%m%d-%H%M%S')
SWAP_BACKUP="${SWAP_FILE}.resize-backup-${TIMESTAMP}"
COUNTER=0

while [ -e "$SWAP_BACKUP" ]; do
    COUNTER=$((COUNTER + 1))
    SWAP_BACKUP="${SWAP_FILE}.resize-backup-${TIMESTAMP}-${COUNTER}"
done

FSTAB_SNAPSHOT=$(mktemp "${FSTAB_FILE}.swap-resize.XXXXXX")
if ! cp -p "$FSTAB_FILE" "$FSTAB_SNAPSHOT"; then
    rm -f -- "$FSTAB_SNAPSHOT"
    echo "Error: could not create a temporary snapshot of $FSTAB_FILE"
    exit 1
fi
OLD_SWAP_MOVED=0

rollback() {
    STATUS="${1:-1}"
    trap - ERR INT TERM
    set +e

    echo "Error: swap resize failed; restoring the previous configuration." >&2

    if [ "$OLD_SWAP_MOVED" -eq 1 ]; then
        if swapon --noheadings --raw --show=NAME | grep -Fxq -- "$SWAP_FILE"; then
            swapoff "$SWAP_FILE"
        fi

        rm -f -- "$SWAP_FILE"

        if [ -f "$SWAP_BACKUP" ]; then
            mv -- "$SWAP_BACKUP" "$SWAP_FILE"
        fi
    fi

    if [ -f "$FSTAB_SNAPSHOT" ]; then
        cp -p "$FSTAB_SNAPSHOT" "$FSTAB_FILE"
        rm -f -- "$FSTAB_SNAPSHOT"
    fi

    if [ "$WAS_ACTIVE" -eq 1 ] && [ -f "$SWAP_FILE" ]; then
        swapon "$SWAP_FILE"
    fi

    exit "$STATUS"
}

trap 'rollback $?' ERR
trap 'rollback 130' INT
trap 'rollback 143' TERM

"$DISABLE_SCRIPT"
mv -- "$SWAP_FILE" "$SWAP_BACKUP"
OLD_SWAP_MOVED=1
"$ENABLE_SCRIPT" "$SIZE"

trap - ERR INT TERM

if ! rm -f -- "$SWAP_BACKUP"; then
    echo "Warning: resize succeeded, but the old swap file could not be removed: $SWAP_BACKUP" >&2
fi

if ! rm -f -- "$FSTAB_SNAPSHOT"; then
    echo "Warning: resize succeeded, but the temporary fstab snapshot could not be removed: $FSTAB_SNAPSHOT" >&2
fi

echo "Swap resized successfully: $SWAP_FILE ($SIZE)"
swapon --show
