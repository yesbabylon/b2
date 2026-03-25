#!/bin/bash

# Check if a username is provided
if [ -z "$1" ]; then
    echo "Usage: $0 USERNAME"
    exit 1
fi

# === CONFIGURATION ===
MAX_SIZE_MB=100

USERNAME="$1"
FILE="/home/$USERNAME/www/log/equal.log"

MAX_SIZE_BYTES=$((MAX_SIZE_MB * 1024 * 1024))
TMP_INVERSE=$(mktemp)
TMP_FINAL=$(mktemp)

# === CHECKS ===
[ ! -f "$FILE" ] && echo "File not found: $FILE" >&2 && exit 1
CURRENT_SIZE=$(stat -c%s "$FILE")
[ "$CURRENT_SIZE" -le "$MAX_SIZE_BYTES" ] && echo "No action needed. File size is $CURRENT_SIZE bytes." && exit 0
echo "Starting log truncation (fast 2-pass mode)..."
echo "Current size: $CURRENT_SIZE bytes, limit: $MAX_SIZE_BYTES bytes."

# === PASS 1: write recent lines in reverse order, stop after threshold ===
SIZE=0
tac "$FILE" | {
  while IFS= read -r LINE; do
    LINE_BYTES=$(( ${#LINE} + 1 ))  # +1 for newline
    SIZE=$((SIZE + LINE_BYTES))
    echo "$LINE" >> "$TMP_INVERSE"
    [ "$SIZE" -ge "$MAX_SIZE_BYTES" ] && break
  done
}

# === PASS 2: restore correct line order ===
tac "$TMP_INVERSE" > "$TMP_FINAL"

# === OVERWRITE ORIGINAL FILE ===
: > "$FILE"
cat "$TMP_FINAL" >> "$FILE"

# === CLEANUP ===
rm -f "$TMP_INVERSE" "$TMP_FINAL"
echo "Log truncated. Approximate retained size: $MAX_SIZE_BYTES bytes."