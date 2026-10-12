#!/bin/bash

set -euo pipefail

if [ "$#" -lt 1 ] || [ "$#" -gt 2 ]; then
    echo "Usage: $0 <IPV4_ADDRESS> [full]" >&2
    echo "full: display individual NGINX request details" >&2
    echo "Example: $0 109.135.28.87" >&2
    echo "With details: $0 109.135.28.87 full" >&2
    exit 1
fi

IP=$1
SHOW_DETAILS=${2:-false}

if [ "$SHOW_DETAILS" != "false" ] && [ "$SHOW_DETAILS" != "full" ]; then
    echo "Error: the optional second argument must be 'full'." >&2
    exit 1
fi

if [[ ! "$IP" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; then
    echo "Error: invalid IPv4 address: $IP" >&2
    exit 1
fi

IFS='.' read -r -a ip_parts <<< "$IP"
for part in "${ip_parts[@]}"; do
    if ((10#$part > 255)); then
        echo "Error: invalid IPv4 address: $IP" >&2
        exit 1
    fi
done

for command_name in zgrep awk fail2ban-regex mktemp; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Error: $command_name is not installed or is not available in PATH." >&2
        exit 1
    fi
done

script_directory=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
filter_directory="$script_directory/../conf/etc/fail2ban/filter.d"

if [ ! -d "$filter_directory" ]; then
    filter_directory=/etc/fail2ban/filter.d
fi

if [ ! -d "$filter_directory" ]; then
    echo "Error: Fail2Ban filter directory not found." >&2
    exit 1
fi

declare -a sudo_command=()
if [ "${EUID:-$(id -u)}" -ne 0 ]; then
    if ! command -v sudo >/dev/null 2>&1; then
        echo "Error: this script must be run as root or sudo must be available." >&2
        exit 1
    fi

    sudo_command=(sudo)
fi

echo "Fail2Ban ban events for $IP"
echo "========================================"

if ban_events=$("${sudo_command[@]}" zgrep -hF "Ban $IP" /var/log/fail2ban.log* 2>&1); then
    :
else
    search_status=$?
    if [ "$search_status" -gt 1 ]; then
        printf '%s\n' "$ban_events" >&2
        exit "$search_status"
    fi
    ban_events=
fi

if [ -n "$ban_events" ]; then
    printf '%s\n' "$ban_events"
else
    echo "No ban event found in /var/log/fail2ban.log*."
fi

echo
echo "NGINX requests matching Fail2Ban filters for $IP"
echo "========================================"

if nginx_requests=$("${sudo_command[@]}" zgrep -hF "$IP" /var/log/nginx/access.log* 2>&1); then
    :
else
    search_status=$?
    if [ "$search_status" -gt 1 ]; then
        printf '%s\n' "$nginx_requests" >&2
        exit "$search_status"
    fi
    nginx_requests=
fi

matched_nginx_requests=

if [ -n "$nginx_requests" ]; then
    requests_file=
    matches_file=

    cleanup_temp_files() {
        if [ -n "$requests_file" ]; then
            rm -f -- "$requests_file"
        fi
        if [ -n "$matches_file" ]; then
            rm -f -- "$matches_file"
        fi
    }

    trap cleanup_temp_files EXIT
    requests_file=$(mktemp)
    matches_file=$(mktemp)
    printf '%s\n' "$nginx_requests" > "$requests_file"

    filter_count=0
    for filter_file in "$filter_directory"/*.conf; do
        if [ ! -f "$filter_file" ]; then
            continue
        fi

        filter_count=$((filter_count + 1))
        filter_name=${filter_file##*/}
        filter_name=${filter_name%.conf}

        if filter_output=$(LC_ALL=C fail2ban-regex --print-all-matched "$requests_file" "$filter_file" 2>&1); then
            :
        else
            filter_status=$?
            echo "Error: unable to apply Fail2Ban filter: $filter_file" >&2
            printf '%s\n' "$filter_output" >&2
            exit "$filter_status"
        fi

        printf '%s\n' "$filter_output" | awk -v filter="$filter_name" '
            /^\|- Matched line\(s\):$/ {
                print_matches = 1
                next
            }
            print_matches && /^`-$/ {
                print_matches = 0
                next
            }
            print_matches {
                sub(/^\|  /, "")
                print filter "\t" $0
            }
        ' >> "$matches_file"
    done

    if [ "$filter_count" -eq 0 ]; then
        echo "Error: no Fail2Ban filter found in $filter_directory." >&2
        exit 1
    fi

    if [ -s "$matches_file" ]; then
        matched_nginx_requests=$(awk '
            NR == FNR {
                separator = index($0, "\t")
                filter = substr($0, 1, separator - 1)
                line = substr($0, separator + 1)
                match_number = ++matches_by_filter[filter SUBSEP line]
                if (match_number > wanted_matches[line]) {
                    wanted_matches[line] = match_number
                }
                next
            }
            emitted_matches[$0] < wanted_matches[$0] {
                print
                emitted_matches[$0]++
            }
        ' "$matches_file" "$requests_file")
    fi

    cleanup_temp_files
    trap - EXIT
fi

if [ -n "$matched_nginx_requests" ]; then
    if [ "$SHOW_DETAILS" = "full" ]; then
        printf '%s\n' "$matched_nginx_requests" | awk -F'"' '{
            print "Date/IP : " $1
            print "Requête : " $2
            print "Referer : " $4
            print "User-Agent : " $6
            print ""
        }'
    else
        printf '%s\n' "$matched_nginx_requests" | awk '
            match($0, /\[[^:]+/) {
                date = substr($0, RSTART + 1, RLENGTH - 1)
                if (!(date in request_count)) {
                    dates[++date_count] = date
                }
                request_count[date]++
            }
            END {
                printf "%-12s | %s\n", "Date", "Requêtes"
                printf "-------------+----------\n"
                for (i = 1; i <= date_count; i++) {
                    date = dates[i]
                    printf "%-12s | %d\n", date, request_count[date]
                }
            }
        '
    fi
else
    echo "No request matching a Fail2Ban filter found in /var/log/nginx/access.log*."
fi
