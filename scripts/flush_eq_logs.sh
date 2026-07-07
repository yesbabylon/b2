#!/bin/bash

# Usage:
#   ./truncate_equal_log.sh USERNAME [MAX_SIZE_MB]
#
# Example:
#   ./truncate_equal_log.sh kaleo.discope.run
#   ./truncate_equal_log.sh kaleo.discope.run 200

set -e

# === ARGUMENTS ===

if [ -z "$1" ]; then
    echo "Usage: $0 USERNAME [MAX_SIZE_MB]"
    exit 1
fi

USERNAME="$1"
MAX_SIZE_MB="${2:-100}"

# === CONFIGURATION ===

EQ_PATH="/home/$USERNAME/www"
LOG_FILE="$EQ_PATH/log/equal.log"
MAX_SIZE_BYTES=$((MAX_SIZE_MB * 1024 * 1024))
TMP_FINAL=$(mktemp)

# === CLEANUP ON EXIT ===

cleanup() {
    rm -f "$TMP_FINAL"
}

trap cleanup EXIT

# === CHECKS ===

if [ ! -d "$EQ_PATH" ]; then
    echo "Error: eQual directory $EQ_PATH not found."
    exit 1
fi

if [ ! -f "$LOG_FILE" ]; then
    echo "Error: Log file $LOG_FILE not found."
    exit 1
fi

if ! [[ "$MAX_SIZE_MB" =~ ^[0-9]+$ ]]; then
    echo "Error: MAX_SIZE_MB must be a positive integer."
    exit 1
fi

if [ "$MAX_SIZE_MB" -le 0 ]; then
    echo "Error: MAX_SIZE_MB must be greater than 0."
    exit 1
fi

# === SIZE CHECK ===

CURRENT_SIZE=$(stat -c%s "$LOG_FILE")

if [ "$CURRENT_SIZE" -le "$MAX_SIZE_BYTES" ]; then
    echo "No action needed."
    echo "File: $LOG_FILE"
    echo "Current size: $CURRENT_SIZE bytes"
    echo "Limit: $MAX_SIZE_BYTES bytes"
    exit 0
fi

echo "Starting JSONL log truncation..."
echo "File: $LOG_FILE"
echo "Current size: $CURRENT_SIZE bytes"
echo "Limit: $MAX_SIZE_BYTES bytes"

# === TRUNCATE JSONL LOG ===
# Keep the last MAX_SIZE_BYTES bytes, then remove the first possibly incomplete JSON line.

tail -c "$MAX_SIZE_BYTES" "$LOG_FILE" | sed '1d' > "$TMP_FINAL"

# Preserve inode for processes still writing to the log.
: > "$LOG_FILE"
cat "$TMP_FINAL" >> "$LOG_FILE"

FINAL_SIZE=$(stat -c%s "$LOG_FILE")

echo "Log truncated."
echo "Final size: $FINAL_SIZE bytes"