#!/bin/bash
set -e

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

IMAGE_NAME="b2-$(basename -- "$(dirname -- "$SCRIPT_DIR")")"
IMAGE_VERSION="$(basename -- "$SCRIPT_DIR")"

docker build -t "${IMAGE_NAME}:${IMAGE_VERSION}" "$SCRIPT_DIR"
