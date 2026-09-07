#!/bin/bash

set -Eeuo pipefail

DEFAULT_CERTIFICATES_DIR="/srv/docker/nginx/certs"

usage() {
    cat <<EOF
Usage: $0 USERNAME archive.tar.gz [certificates-directory]

Imports only the NGINX certificate files belonging to USERNAME. USERNAME must
be the instance's fully qualified domain name (for example: doc.fmtsolutions.be).
Unrelated certificates in the archive and destination are left untouched.
Existing files for USERNAME are backed up before they are replaced.

Default certificates-directory: ${DEFAULT_CERTIFICATES_DIR}
EOF
}

is_valid_username() {
    local username="$1"
    local label
    local -a labels

    [ ${#username} -le 253 ] || return 1
    [[ "$username" == *.* ]] || return 1
    [[ "$username" != .* && "$username" != *. ]] || return 1
    IFS='.' read -r -a labels <<< "$username"
    for label in "${labels[@]}"; do
        [ ${#label} -le 63 ] || return 1
        [[ "$label" =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$ ]] || return 1
    done
}

if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
    usage
    exit 0
fi

if [ $# -lt 2 ] || [ $# -gt 3 ]; then
    usage >&2
    exit 1
fi

username="$1"
archive="$2"
certificates_dir="${3:-$DEFAULT_CERTIFICATES_DIR}"

if ! is_valid_username "$username"; then
    echo "Error: invalid USERNAME (expected a fully qualified domain name): $username" >&2
    exit 1
fi

for command_name in tar find cp mktemp realpath readlink; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Error: required command not found: $command_name" >&2
        exit 1
    fi
done

if [ ! -f "$archive" ]; then
    echo "Error: archive not found: $archive" >&2
    exit 1
fi

archive="$(cd "$(dirname "$archive")" && pwd -P)/$(basename "$archive")"

if ! tar -tzf "$archive" >/dev/null; then
    echo "Error: invalid or unreadable gzip tar archive: $archive" >&2
    exit 1
fi

# Reject paths which could escape the temporary extraction directory.
while IFS= read -r entry; do
    normalized_entry="${entry#./}"
    case "$normalized_entry" in
        /*|../*|*/../*|*/..)
            echo "Error: unsafe path in archive: $entry" >&2
            exit 1
            ;;
    esac
done < <(tar -tzf "$archive")

staging_dir="$(mktemp -d)"
cleanup() {
    rm -rf -- "$staging_dir"
}
trap cleanup EXIT

tar --no-same-owner -xzf "$archive" -C "$staging_dir"

unexpected_type="$(find "$staging_dir" -mindepth 1 ! -type f ! -type d ! -type l -print -quit)"
if [ -n "$unexpected_type" ]; then
    echo "Error: unsupported file type in archive: $unexpected_type" >&2
    exit 1
fi

certificate_found=false
if [ -f "$staging_dir/$username.crt" ] || \
   [ -f "$staging_dir/$username/fullchain.pem" ] || \
   [ -f "$staging_dir/$username/cert.pem" ]; then
    certificate_found=true
fi

if [ "$certificate_found" != true ]; then
    echo "Error: archive does not contain a certificate for instance '$username'." >&2
    exit 1
fi

if [ -L "$staging_dir/$username" ] || \
   { [ -e "$staging_dir/$username" ] && [ ! -d "$staging_dir/$username" ]; }; then
    echo "Error: invalid certificate directory for instance '$username' in archive." >&2
    exit 1
fi

certificate_entries=()
for entry in \
    "$username" \
    "$username.crt" \
    "$username.key" \
    "$username.chain.pem" \
    "$username.dhparam.pem"; do
    if [ -e "$staging_dir/$entry" ] || [ -L "$staging_dir/$entry" ]; then
        certificate_entries+=("$entry")
    fi
done

# Relative links in nginx-proxy's layout must resolve inside the archive.
staging_root="$(realpath "$staging_dir")"
for entry in "${certificate_entries[@]}"; do
    while IFS= read -r -d '' link; do
        link_target="$(readlink "$link")"
        if [[ "$link_target" = /* ]]; then
            echo "Error: absolute symlink is not allowed: $link -> $link_target" >&2
            exit 1
        fi
        if ! resolved_target="$(realpath -e "$link")"; then
            echo "Error: broken symlink in archive: $link -> $link_target" >&2
            exit 1
        fi
        case "$resolved_target" in
            "$staging_root/$username"|"$staging_root/$username"/*) ;;
            *)
                echo "Error: certificate symlink escapes the instance directory: $link -> $link_target" >&2
                exit 1
                ;;
        esac
    done < <(find "$staging_dir/$entry" -type l -print0)
done

if [ -e "$certificates_dir" ] && [ ! -d "$certificates_dir" ]; then
    echo "Error: destination exists but is not a directory: $certificates_dir" >&2
    exit 1
fi

mkdir -p -- "$certificates_dir"
certificates_dir="$(cd "$certificates_dir" && pwd -P)"

existing_entries=()
for entry in \
    "$username" \
    "$username.crt" \
    "$username.key" \
    "$username.chain.pem" \
    "$username.dhparam.pem"; do
    if [ -e "$certificates_dir/$entry" ] || [ -L "$certificates_dir/$entry" ]; then
        existing_entries+=("$entry")
    fi
done

if [ ${#existing_entries[@]} -gt 0 ]; then
    parent_dir="$(dirname "$certificates_dir")"
    directory_name="$(basename "$certificates_dir")"
    backup="${parent_dir}/${directory_name}-${username}-backup-$(date +%Y%m%d-%H%M%S).tar.gz"
    if [ -e "$backup" ]; then
        backup="${backup%.tar.gz}-$$.tar.gz"
    fi

    umask 077
    tar -C "$certificates_dir" -czf "$backup" -- "${existing_entries[@]}"
    chmod 600 "$backup"
    echo "Current certificate for '$username' backed up to: $backup"

    for entry in "${existing_entries[@]}"; do
        rm -rf -- "$certificates_dir/$entry"
    done
fi

for entry in "${certificate_entries[@]}"; do
    cp -a -- "$staging_dir/$entry" "$certificates_dir/"
done

echo "Certificate import successful for instance '$username': $certificates_dir"
echo "Reload or restart nginx-proxy to use the imported certificate."
