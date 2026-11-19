#!/bin/bash

set -Eeuo pipefail

usage() {
    echo "Usage: $0 [--recipient RECIPIENT] PRIVATE_KEY_FILE"
}

if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
    usage
    exit 0
fi

case $# in
    1)
        recipient="$(hostname)"
        private_key_file="$1"
        ;;
    3)
        if [ "$1" != "--recipient" ]; then
            usage >&2
            exit 1
        fi
        recipient="$2"
        private_key_file="$3"
        ;;
    *)
        usage >&2
        exit 1
        ;;
esac

if [ "$EUID" -ne 0 ]; then
    echo "Error: this tool must be run as root." >&2
    exit 1
fi

if ! command -v gpg >/dev/null 2>&1; then
    echo "Error: required command not found: gpg" >&2
    exit 1
fi

if [ -z "$recipient" ]; then
    echo "Error: recipient cannot be empty." >&2
    exit 1
fi

if [ ! -f "$private_key_file" ] || [ ! -r "$private_key_file" ]; then
    echo "Error: private key file not found or not readable: $private_key_file" >&2
    exit 1
fi

gpg_home="/root/.gnupg"
if [ -L "$gpg_home" ] || { [ -e "$gpg_home" ] && [ ! -d "$gpg_home" ]; }; then
    echo "Error: invalid root GPG home directory: $gpg_home" >&2
    exit 1
fi
mkdir -p -- "$gpg_home"
chmod 700 "$gpg_home"

if ! key_info="$(
    gpg --homedir "$gpg_home" --batch --with-colons \
        --import-options show-only --import "$private_key_file" 2>/dev/null
)"; then
    echo "Error: invalid GPG private key file: $private_key_file" >&2
    exit 1
fi

if [ "$(grep -c '^sec:' <<< "$key_info")" -ne 1 ]; then
    echo "Error: the file must contain exactly one private GPG key." >&2
    exit 1
fi

if ! awk -F: -v recipient="$recipient" '
    BEGIN { recipient = tolower(recipient) }
    $1 == "uid" && index(tolower($10), recipient) { found = 1 }
    END { exit !found }
' <<< "$key_info"; then
    echo "Error: the private key does not match recipient: $recipient" >&2
    exit 1
fi

gpg --homedir "$gpg_home" --batch --import "$private_key_file"
echo "Private GPG key imported for recipient: $recipient"
