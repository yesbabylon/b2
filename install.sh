#!/bin/bash

. /etc/os-release

case "$VERSION_ID" in
    24.04)
        exec ./install/ubuntu-24.04.sh
        ;;
    26.04)
        exec ./install/ubuntu-26.04.sh
        ;;
    *)
        echo "Unsupported Ubuntu version: $VERSION_ID"
        exit 1
        ;;
esac