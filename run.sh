#!/bin/bash

if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP first."
    exit 1
fi

cd src
php run.php "$@"
