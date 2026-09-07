#!/bin/bash

set -Eeuo pipefail

DEFAULT_CERTIFICATES_DIR="/srv/docker/nginx/certs"

usage() {
    cat <<EOF
Usage: $0 USERNAME [output.tar.gz] [certificates-directory]

Exports only the NGINX certificate files belonging to USERNAME. USERNAME must
be the instance's fully qualified domain name (for example: doc.fmtsolutions.be).
Symlinks, file modes and the instance directory structure are preserved.

Defaults:
  output                    ./certificates-<USERNAME>-<date>.tar.gz
  certificates-directory   ${DEFAULT_CERTIFICATES_DIR}
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

if [ $# -lt 1 ] || [ $# -gt 3 ]; then
    usage >&2
    exit 1
fi

username="$1"
if ! is_valid_username "$username"; then
    echo "Error: invalid USERNAME (expected a fully qualified domain name): $username" >&2
    exit 1
fi

default_output="certificates-${username}-$(date +%Y%m%d-%H%M%S).tar.gz"
output="${2:-$default_output}"
certificates_dir="${3:-$DEFAULT_CERTIFICATES_DIR}"

for command_name in tar find mktemp realpath readlink; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Error: required command not found: $command_name" >&2
        exit 1
    fi
done

if [ ! -d "$certificates_dir" ]; then
    echo "Error: certificates directory not found: $certificates_dir" >&2
    exit 1
fi

certificates_dir="$(cd "$certificates_dir" && pwd -P)"
output_dir="$(dirname "$output")"
output_name="$(basename "$output")"

if [ ! -d "$output_dir" ]; then
    echo "Error: output directory not found: $output_dir" >&2
    exit 1
fi

output_dir="$(cd "$output_dir" && pwd -P)"
output="${output_dir}/${output_name}"

if [ -e "$output" ]; then
    echo "Error: output file already exists: $output" >&2
    exit 1
fi

case "$output" in
    "$certificates_dir"/*)
        echo "Error: the archive cannot be created inside the certificates directory." >&2
        exit 1
        ;;
esac

certificate_found=false
if [ -f "$certificates_dir/$username.crt" ] || \
   [ -f "$certificates_dir/$username/fullchain.pem" ] || \
   [ -f "$certificates_dir/$username/cert.pem" ]; then
    certificate_found=true
fi

if [ "$certificate_found" != true ]; then
    echo "Error: no certificate found for instance '$username' in $certificates_dir" >&2
    exit 1
fi

if [ -L "$certificates_dir/$username" ] || \
   { [ -e "$certificates_dir/$username" ] && [ ! -d "$certificates_dir/$username" ]; }; then
    echo "Error: expected certificate directory for '$username': $certificates_dir/$username" >&2
    exit 1
fi

certificate_entries=()
for entry in \
    "$username" \
    "$username.crt" \
    "$username.key" \
    "$username.chain.pem" \
    "$username.dhparam.pem"; do
    if [ -e "$certificates_dir/$entry" ] || [ -L "$certificates_dir/$entry" ]; then
        certificate_entries+=("$entry")
    fi
done

# Some nginx-proxy installations use one shared DH-parameter file and link
# <USERNAME>.dhparam.pem to ./dhparam.pem. Include that dependency so the
# exported archive remains self-contained.
shared_dhparam_path="$certificates_dir/dhparam.pem"
if [ -L "$certificates_dir/$username.dhparam.pem" ] && \
   resolved_dhparam="$(realpath -e "$certificates_dir/$username.dhparam.pem" 2>/dev/null)" && \
   [ "$resolved_dhparam" = "$shared_dhparam_path" ]; then
    certificate_entries+=("dhparam.pem")
fi

# Ensure exported symlinks are usable and stay inside the certificates tree.
for entry in "${certificate_entries[@]}"; do
    while IFS= read -r -d '' link; do
        link_target="$(readlink "$link")"
        if [[ "$link_target" = /* ]]; then
            echo "Error: absolute certificate symlink is not allowed: $link -> $link_target" >&2
            exit 1
        fi
        if ! resolved_target="$(realpath -e "$link")"; then
            echo "Error: broken certificate symlink: $link -> $link_target" >&2
            exit 1
        fi
        case "$resolved_target" in
            "$certificates_dir/$username"|"$certificates_dir/$username"/*) ;;
            "$shared_dhparam_path")
                if [ "$link" != "$certificates_dir/$username.dhparam.pem" ]; then
                    echo "Error: unexpected symlink to shared DH parameters: $link -> $link_target" >&2
                    exit 1
                fi
                ;;
            *)
                echo "Error: certificate symlink escapes the instance directory: $link -> $link_target" >&2
                exit 1
                ;;
        esac
    done < <(find "$certificates_dir/$entry" -type l -print0)
done

# Certificate archives contain private keys. Restrict newly created files even
# if the calling shell has a permissive umask.
umask 077
temporary_archive="$(mktemp "${output}.tmp.XXXXXX")"

cleanup() {
    rm -f -- "$temporary_archive"
}
trap cleanup EXIT

echo "Exporting certificate for instance: $username"
echo "Certificates directory: $certificates_dir"
echo "Archive: $output"

tar -C "$certificates_dir" -czf "$temporary_archive" -- "${certificate_entries[@]}"
tar -tzf "$temporary_archive" >/dev/null
chmod 600 "$temporary_archive"
mv -- "$temporary_archive" "$output"
trap - EXIT

echo "Certificate export successful: $output"
echo "Keep this archive secure: it contains a private key."
