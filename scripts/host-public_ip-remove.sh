#!/bin/bash

set -e

NETPLAN_FILE="/etc/netplan/51-failover.yaml"
PREFIX="24"

# Check argument
if [ "$#" -ne 1 ]; then
    echo "Usage: sudo $0 <PUBLIC_IP>"
    exit 1
fi

if [[ "$1" == */* ]]; then
    echo "Error: provide the IPv4 address without a prefix. /$PREFIX is added automatically."
    exit 1
fi

# Check privileges
if [ "$(id -u)" -ne 0 ]; then
    echo "Error: this script must be run as root."
    exit 1
fi

# Check Netplan file
if [ ! -f "$NETPLAN_FILE" ]; then
    echo "Error: Netplan file not found: $NETPLAN_FILE"
    exit 1
fi

ADDRESS="$1/$PREFIX"

# Prepare the modified file without touching the original
TEMP_FILE=$(mktemp "${NETPLAN_FILE}.tmp.XXXXXX")
trap 'rm -f "$TEMP_FILE"' EXIT

if ! awk -v address="$ADDRESS" '
$0 == "    veth0:" {
    in_veth0 = 1
}

in_veth0 && /^    [^ ]/ && $0 != "    veth0:" {
    in_veth0 = 0
}

in_veth0 && /^      addresses: \[[^]]+\]$/ {
    if (format_seen) {
        failed = 1
        exit
    }
    format_seen = 1

    opening_bracket = index($0, "[")
    prefix = substr($0, 1, opening_bracket)
    values = substr($0, opening_bracket + 1)
    sub(/\]$/, "", values)

    count = split(values, items, ",")
    new_values = ""

    for (i = 1; i <= count; i++) {
        item = items[i]
        gsub(/^[[:space:]]+|[[:space:]]+$/, "", item)

        if (item == address) {
            removed = 1
        } else {
            if (new_values != "") {
                new_values = new_values ", "
            }
            new_values = new_values item
        }
    }

    if (removed) {
        print prefix new_values "]"
        next
    }
}

{ print }

END {
    if (failed || !format_seen) {
        exit 1
    }
}
' "$NETPLAN_FILE" > "$TEMP_FILE"; then
    echo "Error: unsupported addresses format in $NETPLAN_FILE"
    exit 1
fi

# Do nothing if the address is not present on veth0
if cmp -s "$NETPLAN_FILE" "$TEMP_FILE"; then
    echo "Address $ADDRESS is not present."
    exit 0
fi

# Create a dated backup
TIMESTAMP=$(date '+%Y%m%d-%H%M%S')
BACKUP_FILE="${NETPLAN_FILE}.bak-${TIMESTAMP}"
COUNTER=0

while [ -e "$BACKUP_FILE" ]; do
    COUNTER=$((COUNTER + 1))
    BACKUP_FILE="${NETPLAN_FILE}.bak-${TIMESTAMP}-${COUNTER}"
done

cp -p "$NETPLAN_FILE" "$BACKUP_FILE"
chmod --reference="$NETPLAN_FILE" "$TEMP_FILE"
chown --reference="$NETPLAN_FILE" "$TEMP_FILE"
mv "$TEMP_FILE" "$NETPLAN_FILE"

# Validate the new configuration
if ! netplan generate; then
    cp -p "$BACKUP_FILE" "$NETPLAN_FILE"
    echo "Error: Netplan validation failed. The original file has been restored."
    exit 1
fi

# Recreate veth0 and apply the configuration
if ip link show veth0 >/dev/null 2>&1; then
    ip link delete veth0
fi
netplan apply

echo "Address $ADDRESS removed from veth0."
echo "Backup created: $BACKUP_FILE"
