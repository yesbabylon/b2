#!/bin/bash

set -euo pipefail

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <IPV4_PATTERN>" >&2
    echo "Example: $0 '192.168.*'" >&2
    exit 1
fi

IP_PATTERN=$1

if ! command -v fail2ban-client >/dev/null 2>&1; then
    echo "Error: fail2ban-client is not installed or is not available in PATH." >&2
    exit 1
fi

# The wildcard must be the last component and matches the rest of the address.
if [[ ! "$IP_PATTERN" =~ ^([0-9]{1,3}\.){0,3}([0-9]{1,3}|\*)$ ]]; then
    echo "Error: invalid IPv4 pattern: $IP_PATTERN" >&2
    exit 1
fi

IFS='.' read -r -a pattern_parts <<< "$IP_PATTERN"
has_wildcard=false

for part in "${pattern_parts[@]}"; do
    if [ "$part" = "*" ]; then
        has_wildcard=true
        continue
    fi

    if ((10#$part > 255)); then
        echo "Error: invalid IPv4 pattern: $IP_PATTERN" >&2
        exit 1
    fi
done

if [ "$has_wildcard" = false ] && [ "${#pattern_parts[@]}" -ne 4 ]; then
    echo "Error: invalid IPv4 pattern: $IP_PATTERN" >&2
    exit 1
fi

if [ "$has_wildcard" = true ]; then
    IP_PREFIX=${IP_PATTERN%\*}
else
    IP_PREFIX=$IP_PATTERN
fi

status=$(fail2ban-client status)
jail_list=$(printf '%s\n' "$status" | sed -n 's/^.*Jail list:[[:space:]]*//p')

declare -a json_entries=()

IFS=',' read -r -a jails <<< "$jail_list"
for jail in "${jails[@]}"; do
    jail=${jail#"${jail%%[![:space:]]*}"}
    jail=${jail%"${jail##*[![:space:]]}"}

    [ -n "$jail" ] || continue

    banned_ips=$(fail2ban-client get "$jail" banip)
    banned_ips=${banned_ips//$'\n'/ }
    read -r -a banned_ip_list <<< "$banned_ips"
    unbanned_count=0

    for banned_ip in "${banned_ip_list[@]}"; do
        if [[ ! "$banned_ip" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; then
            continue
        fi

        if { [ "$has_wildcard" = true ] && [[ "$banned_ip" == "$IP_PREFIX"* ]]; } || \
           { [ "$has_wildcard" = false ] && [ "$banned_ip" = "$IP_PATTERN" ]; }; then
            fail2ban-client set "$jail" unbanip "$banned_ip" >/dev/null
            ((unbanned_count += 1))
        fi
    done

    if ((unbanned_count > 0)); then
        escaped_jail=${jail//\\/\\\\}
        escaped_jail=${escaped_jail//\"/\\\"}
        json_entries+=("\"$escaped_jail\":$unbanned_count")
    fi
done

printf '{'
if ((${#json_entries[@]} > 0)); then
    printf '%s' "${json_entries[0]}"
    for entry in "${json_entries[@]:1}"; do
        printf ',%s' "$entry"
    done
fi
printf '}\n'
