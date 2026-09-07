#!/bin/bash

set -Eeuo pipefail

DEFAULT_CERTIFICATES_DIR="/srv/docker/nginx/certs"

usage() {
    cat <<EOF
Usage: $0 archive.tar.gz [certificates-directory]

Imports an archive produced by certificate-export.sh. Existing files with the
same names are replaced; unrelated files are kept. A backup of a non-empty
destination is created next to the certificates directory before import.

Default certificates-directory: ${DEFAULT_CERTIFICATES_DIR}
EOF
}

if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
    usage
    exit 0
fi

if [ $# -lt 1 ] || [ $# -gt 2 ]; then
    usage >&2
    exit 1
fi

archive="$1"
certificates_dir="${2:-$DEFAULT_CERTIFICATES_DIR}"

for command_name in tar find grep cp mktemp realpath readlink; do
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

has_certificate_file=false
while IFS= read -r -d '' candidate; do
    case "$candidate" in
        *.crt|*.key|*.pem)
            has_certificate_file=true
            break
            ;;
    esac
done < <(find "$staging_dir" -mindepth 1 \( -type f -o -type l \) -print0)

if [ "$has_certificate_file" != true ]; then
    echo "Error: archive does not contain any .crt, .key or .pem file." >&2
    exit 1
fi

# The relative links in nginx-proxy's certificate layout must resolve inside
# the archive and must not be broken.
staging_root="$(realpath "$staging_dir")"
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
        "$staging_root"/*) ;;
        *)
            echo "Error: symlink escapes the archive: $link -> $link_target" >&2
            exit 1
            ;;
    esac
done < <(find "$staging_dir" -type l -print0)

if [ -e "$certificates_dir" ] && [ ! -d "$certificates_dir" ]; then
    echo "Error: destination exists but is not a directory: $certificates_dir" >&2
    exit 1
fi

mkdir -p -- "$certificates_dir"
certificates_dir="$(cd "$certificates_dir" && pwd -P)"

if find "$certificates_dir" -mindepth 1 -print -quit | grep -q .; then
    parent_dir="$(dirname "$certificates_dir")"
    directory_name="$(basename "$certificates_dir")"
    backup="${parent_dir}/${directory_name}-backup-$(date +%Y%m%d-%H%M%S).tar.gz"
    if [ -e "$backup" ]; then
        backup="${backup%.tar.gz}-$$.tar.gz"
    fi

    umask 077
    tar -C "$certificates_dir" -czf "$backup" .
    chmod 600 "$backup"
    echo "Current certificates backed up to: $backup"
fi

cp -a -- "$staging_dir/." "$certificates_dir/"

echo "Certificate import successful: $certificates_dir"
echo "Reload or restart nginx-proxy to use the imported certificates."
