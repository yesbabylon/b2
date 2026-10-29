#!/bin/bash

set -e

NETPLAN_FILE="/etc/netplan/51-failover.yaml"
PREFIX="24"

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
ENV_FILE="$(dirname -- "$SCRIPT_DIR")/.env"

# Check argument
if [ "$#" -ne 1 ]; then
    echo "Usage: sudo $0 <PUBLIC_IP>"
    exit 1
fi

if ! [[ "$1" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; then
    echo "Error: provide a valid IPv4 address without a prefix. /$PREFIX is added automatically."
    exit 1
fi

IFS='.' read -r -a OCTETS <<< "$1"
for OCTET in "${OCTETS[@]}"; do
    if ((10#$OCTET > 255)) || { [ "$OCTET" != "0" ] && [[ "$OCTET" == 0* ]]; }; then
        echo "Error: provide a valid IPv4 address without a prefix. /$PREFIX is added automatically."
        exit 1
    fi
done

# Check privileges
if [ "$(id -u)" -ne 0 ]; then
    echo "Error: this script must be run as root."
    exit 1
fi

NETPLAN_DIR=$(dirname -- "$NETPLAN_FILE")
if [ ! -d "$NETPLAN_DIR" ]; then
    echo "Error: Netplan directory not found: $NETPLAN_DIR"
    exit 1
fi

ADDRESS="$1/$PREFIX"
FILE_EXISTED=false
BACKUP_FILE=""

# If the configuration already exists, only accept an empty inline address list.
if [ -e "$NETPLAN_FILE" ]; then
    if [ ! -f "$NETPLAN_FILE" ]; then
        echo "Error: Netplan path is not a regular file: $NETPLAN_FILE"
        exit 1
    fi

    FILE_EXISTED=true

    if ! CURRENT_ADDRESSES=$(awk '
$0 == "    veth0:" {
    if (veth0_seen) {
        failed = 1
        exit
    }
    veth0_seen = 1
    in_veth0 = 1
}

in_veth0 && /^    [^ ]/ && $0 != "    veth0:" {
    in_veth0 = 0
}

in_veth0 && /^      addresses:[[:space:]]*\[[^]]*\][[:space:]]*$/ {
    if (addresses_seen) {
        failed = 1
        exit
    }
    addresses_seen = 1

    values = $0
    sub(/^[^[]*\[/, "", values)
    sub(/\][[:space:]]*$/, "", values)
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", values)

    if (values != "") {
        count = split(values, items, ",")
        result = ""
        for (i = 1; i <= count; i++) {
            item = items[i]
            gsub(/^[[:space:]]+|[[:space:]]+$/, "", item)
            result = result (result == "" ? "" : ", ") item
        }
        print result
    }
}

END {
    if (failed || !veth0_seen || !addresses_seen) {
        exit 1
    }
}
' "$NETPLAN_FILE"); then
        echo "Error: unsupported veth0 addresses format in $NETPLAN_FILE"
        exit 1
    fi

    if [ -n "$CURRENT_ADDRESSES" ]; then
        echo "Error: public IP configuration already contains: $CURRENT_ADDRESSES"
        echo "Use host-network_public_ip-add.sh to add another address."
        exit 1
    fi
fi

# Prepare the new configuration without touching the original.
TEMP_FILE=$(mktemp "${NETPLAN_FILE}.tmp.XXXXXX")
trap 'rm -f "$TEMP_FILE"' EXIT

if [ "$FILE_EXISTED" = true ]; then
    if ! awk -v address="$ADDRESS" '
$0 == "    veth0:" {
    in_veth0 = 1
}

in_veth0 && /^    [^ ]/ && $0 != "    veth0:" {
    in_veth0 = 0
}

in_veth0 && /^      addresses:[[:space:]]*\[[[:space:]]*\][[:space:]]*$/ {
    if (updated) {
        failed = 1
        exit
    }
    opening_bracket = index($0, "[")
    print substr($0, 1, opening_bracket) address "]"
    updated = 1
    next
}

{ print }

END {
    if (failed || !updated) {
        exit 1
    }
}
' "$NETPLAN_FILE" > "$TEMP_FILE"; then
        echo "Error: unsupported veth0 addresses format in $NETPLAN_FILE"
        exit 1
    fi

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
else
    printf '%s\n' \
        'network:' \
        '  version: 2' \
        '  vlans:' \
        '    veth0:' \
        '      id: 0' \
        '      link: ens3' \
        '      dhcp4: no' \
        "      addresses: [$ADDRESS]" > "$TEMP_FILE"
    chmod 600 "$TEMP_FILE"
fi

mv "$TEMP_FILE" "$NETPLAN_FILE"

restore_configuration() {
    if [ "$FILE_EXISTED" = true ]; then
        cp -p "$BACKUP_FILE" "$NETPLAN_FILE"
    else
        rm -f "$NETPLAN_FILE"
    fi
}

# Validate the new configuration.
if ! netplan generate; then
    restore_configuration
    echo "Error: Netplan validation failed. The original configuration has been restored."
    exit 1
fi

# Apply the configuration and attempt to restore the previous state on failure.
if ! netplan apply; then
    restore_configuration
    if netplan generate && netplan apply; then
        echo "Error: Netplan apply failed. The original configuration has been restored."
    else
        echo "Error: Netplan apply failed, and the original configuration could not be fully reapplied."
    fi
    exit 1
fi

if [ -f "$ENV_FILE" ]; then
    if grep -q '^PUBLIC_IP=' "$ENV_FILE"; then
        sed -i "s/^PUBLIC_IP=.*/PUBLIC_IP=$1/" "$ENV_FILE"
    elif [ -s "$ENV_FILE" ]; then
        sed -i "\$aPUBLIC_IP=$1" "$ENV_FILE"
    else
        printf 'PUBLIC_IP=%s\n' "$1" > "$ENV_FILE"
    fi

    echo "Environment configuration updated: $ENV_FILE"
fi

echo "Address $ADDRESS set on veth0."
if [ "$FILE_EXISTED" = true ]; then
    echo "Backup created: $BACKUP_FILE"
else
    echo "Netplan configuration created: $NETPLAN_FILE"
fi
