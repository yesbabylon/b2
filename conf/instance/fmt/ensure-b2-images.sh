#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
LOCAL_IMAGE_ROOT="/root/b2/conf/docker/images"

cd "$SCRIPT_DIR"

compose_images="$(docker compose config --images)"
declare -A seen_images=()

while IFS= read -r image; do
    [[ "$image" == b2-* ]] || continue
    [[ -z "${seen_images[$image]:-}" ]] || continue
    seen_images["$image"]=1

    if [[ ! "$image" =~ ^b2-([a-z0-9]+([._-][a-z0-9]+)*):([a-zA-Z0-9]+([._-][a-zA-Z0-9]+)*)$ ]]; then
        printf "Invalid local Docker image reference in docker-compose.yml: %s\n" "$image" >&2
        exit 1
    fi

    application="${BASH_REMATCH[1]}"
    version="${BASH_REMATCH[3]}"
    build_dir="$LOCAL_IMAGE_ROOT/$application/$version"

    if docker image inspect "$image" >/dev/null 2>&1; then
        printf "Local Docker image already available: %s\n" "$image"
        continue
    fi

    if [[ ! -x "$build_dir/build.sh" ]]; then
        printf "Missing executable build script for local Docker image %s: %s/build.sh\n" \
            "$image" "$build_dir" >&2
        exit 1
    fi

    printf "Building missing local Docker image: %s\n" "$image"
    (cd "$build_dir" && ./build.sh)

    if ! docker image inspect "$image" >/dev/null 2>&1; then
        printf "Build completed without creating expected local Docker image: %s\n" "$image" >&2
        exit 1
    fi
done <<< "$compose_images"
