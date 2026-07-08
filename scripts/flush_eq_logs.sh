#!/bin/bash

# Usage:
#   ./rotate_equal_log.sh USERNAME [MAX_SIZE_MB] [KEEP_ARCHIVES]
#
# Examples:
#   ./rotate_equal_log.sh kaleo.discope.run
#   ./rotate_equal_log.sh kaleo.discope.run 200
#   ./rotate_equal_log.sh kaleo.discope.run 200 10
#
# Behavior:
#   equal.log   = latest complete JSONL lines, up to roughly MAX_SIZE_MB
#   equal.log.1 = old part removed during the latest rotation
#   equal.log.2 = previous archive
#   etc.

set -euo pipefail

# === ARGUMENTS ===

if [ -z "${1:-}" ]; then
    echo "Usage: $0 USERNAME [MAX_SIZE_MB] [KEEP_ARCHIVES]"
    exit 1
fi

USERNAME="$1"
MAX_SIZE_MB="${2:-100}"
KEEP_ARCHIVES="${3:-5}"

if ! [[ "$MAX_SIZE_MB" =~ ^[0-9]+$ ]] || [ "$MAX_SIZE_MB" -le 0 ]; then
    echo "Error: MAX_SIZE_MB must be a positive integer."
    exit 1
fi

if ! [[ "$KEEP_ARCHIVES" =~ ^[0-9]+$ ]] || [ "$KEEP_ARCHIVES" -le 0 ]; then
    echo "Error: KEEP_ARCHIVES must be a positive integer."
    exit 1
fi

# === CONFIGURATION ===

EQ_PATH="/home/$USERNAME/www"
LOG_DIR="$EQ_PATH/log"
LOG_FILE="$LOG_DIR/equal.log"
LOCK_FILE="$LOG_DIR/.equal.log.rotate.lock"

MAX_SIZE_BYTES=$((MAX_SIZE_MB * 1024 * 1024))

# === CHECKS ===

if [ ! -d "$EQ_PATH" ]; then
    echo "Error: eQual directory $EQ_PATH not found."
    exit 1
fi

if [ ! -d "$LOG_DIR" ]; then
    echo "Error: Log directory $LOG_DIR not found."
    exit 1
fi

if [ ! -f "$LOG_FILE" ]; then
    echo "Error: Log file $LOG_FILE not found."
    exit 1
fi

# === LOCK ===
# Prevent multiple rotations from running at the same time for this log file.

exec 9>"$LOCK_FILE"

if ! flock -n 9; then
    echo "Error: another rotation is already running for $LOG_FILE."
    exit 1
fi

# === TEMP FILES ===

TMP_SNAPSHOT=$(mktemp "$LOG_DIR/.equal.log.snapshot.XXXXXX")
TMP_KEEP=$(mktemp "$LOG_DIR/.equal.log.keep.XXXXXX")
TMP_ARCHIVE=$(mktemp "$LOG_DIR/.equal.log.archive.XXXXXX")
TMP_DELTA=$(mktemp "$LOG_DIR/.equal.log.delta.XXXXXX")

cleanup() {
    rm -f "$TMP_SNAPSHOT" "$TMP_KEEP" "$TMP_ARCHIVE" "$TMP_DELTA"
}

trap cleanup EXIT

# === SIZE CHECK ===

CURRENT_SIZE=$(stat -c%s "$LOG_FILE")

if [ "$CURRENT_SIZE" -le "$MAX_SIZE_BYTES" ]; then
    echo "No action needed."
    echo "File: $LOG_FILE"
    echo "Current size: $CURRENT_SIZE bytes"
    echo "Limit: $MAX_SIZE_BYTES bytes"
    exit 0
fi

echo "Starting JSONL log rotation..."
echo "File: $LOG_FILE"
echo "Current size: $CURRENT_SIZE bytes"
echo "Limit: $MAX_SIZE_BYTES bytes"
echo "Archives kept: $KEEP_ARCHIVES"

# === SNAPSHOT ===
# Work on a logical copy of the file as it was when the script started.
# This makes it possible to split the active part and the archived part safely.

head -c "$CURRENT_SIZE" "$LOG_FILE" > "$TMP_SNAPSHOT"

# === SPLIT JSONL LOG ===
# Keep the last MAX_SIZE_BYTES bytes, then remove the first potentially
# incomplete line so equal.log starts with a complete JSONL line.

tail -c "$MAX_SIZE_BYTES" "$TMP_SNAPSHOT" | sed '1d' > "$TMP_KEEP"

KEEP_SIZE=$(stat -c%s "$TMP_KEEP")
ARCHIVE_BYTES=$((CURRENT_SIZE - KEEP_SIZE))

# The old part removed from equal.log becomes equal.log.1.

if [ "$ARCHIVE_BYTES" -gt 0 ]; then
    head -c "$ARCHIVE_BYTES" "$TMP_SNAPSHOT" > "$TMP_ARCHIVE"
else
    : > "$TMP_ARCHIVE"
fi

ARCHIVE_SIZE=$(stat -c%s "$TMP_ARCHIVE")

# === BEST EFFORT: PRESERVE NEW APPENDS ===
# If the log received new writes during the rotation, append them back to
# equal.log after the retained part.

AFTER_SIZE=$(stat -c%s "$LOG_FILE")

if [ "$AFTER_SIZE" -gt "$CURRENT_SIZE" ]; then
    DELTA_BYTES=$((AFTER_SIZE - CURRENT_SIZE))
    tail -c "$DELTA_BYTES" "$LOG_FILE" > "$TMP_DELTA"
else
    : > "$TMP_DELTA"
fi

DELTA_SIZE=$(stat -c%s "$TMP_DELTA")

# === ROTATE ARCHIVES ===
# equal.log.4 -> equal.log.5
# equal.log.3 -> equal.log.4
# ...
# equal.log.1 -> equal.log.2
# new archive -> equal.log.1

rm -f "$LOG_FILE.$KEEP_ARCHIVES"

for ((i = KEEP_ARCHIVES - 1; i >= 1; i--)); do
    if [ -e "$LOG_FILE.$i" ]; then
        mv "$LOG_FILE.$i" "$LOG_FILE.$((i + 1))"
    fi
done

# Reuse the same permissions and ownership as the active log file whenever possible.

chmod --reference="$LOG_FILE" "$TMP_ARCHIVE" 2>/dev/null || true
chown --reference="$LOG_FILE" "$TMP_ARCHIVE" 2>/dev/null || true

mv "$TMP_ARCHIVE" "$LOG_FILE.1"

# === REWRITE ACTIVE LOG ===
# Preserve the inode of equal.log so running processes can keep writing to it.

: > "$LOG_FILE"
cat "$TMP_KEEP" "$TMP_DELTA" >> "$LOG_FILE"

FINAL_SIZE=$(stat -c%s "$LOG_FILE")

echo "Log rotated."
echo "Archived old part: $LOG_FILE.1"
echo "Archive size: $ARCHIVE_SIZE bytes"
echo "Preserved new appended bytes during rotation: $DELTA_SIZE bytes"
echo "Final active log size: $FINAL_SIZE bytes"