#!/bin/bash

set -Eeuo pipefail

DEFAULT_CERTIFICATES_DIR="/srv/docker/nginx/certs"

usage() {
    cat <<EOF
Usage: $0 [output.tar.gz] [certificates-directory]

Exports the NGINX certificates directory to a gzip-compressed tar archive.
Symlinks, file modes and the directory structure are preserved.

Defaults:
  output                    ./certificates-<host>-<date>.tar.gz
  certificates-directory   ${DEFAULT_CERTIFICATES_DIR}
EOF
}

if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ] || [ $# -gt 2 ]; then
    usage
    [ $# -le 2 ] && exit 0
    exit 1
fi

host_name="$(hostname -s 2>/dev/null || hostname)"
host_name="${host_name//[^a-zA-Z0-9._-]/_}"
default_output="certificates-${host_name}-$(date +%Y%m%d-%H%M%S).tar.gz"

output="${1:-$default_output}"
certificates_dir="${2:-$DEFAULT_CERTIFICATES_DIR}"

for command_name in tar find grep mktemp; do
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

if ! find "$certificates_dir" -mindepth 1 \( -type f -o -type l \) -print -quit | grep -q .; then
    echo "Error: certificates directory is empty: $certificates_dir" >&2
    exit 1
fi

# Certificate archives contain private keys. Restrict newly created files even
# if the calling shell has a permissive umask.
umask 077
temporary_archive="$(mktemp "${output}.tmp.XXXXXX")"

cleanup() {
    rm -f -- "$temporary_archive"
}
trap cleanup EXIT

echo "Exporting certificates from: $certificates_dir"
echo "Archive: $output"

# Archive the directory contents instead of its absolute path. tar preserves
# the relative symlinks used by nginx-proxy.
tar -C "$certificates_dir" -czf "$temporary_archive" .
tar -tzf "$temporary_archive" >/dev/null
chmod 600 "$temporary_archive"
mv -- "$temporary_archive" "$output"
trap - EXIT

echo "Certificate export successful: $output"
echo "Keep this archive secure: it contains private keys."
