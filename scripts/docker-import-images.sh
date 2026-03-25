#!/bin/bash

# Usage:
# ./docker-import-images.sh file.tar[.gz]

INPUT=$1

if [ -z "$INPUT" ]; then
  echo "Usage: $0 file.tar[.gz]"
  exit 1
fi

if [ ! -f "$INPUT" ]; then
  echo "File not found: $INPUT"
  exit 1
fi

echo "Importing images from: $INPUT"

# auto detect format (compression)
if [[ "$INPUT" == *.gz ]]; then
  echo "Detected gzip archive"
  gunzip -c "$INPUT" | docker load
else
  echo "Detected tar archive"
  docker load -i "$INPUT"
fi

if [ $? -ne 0 ]; then
  echo "Import failed"
  exit 1
fi

echo "Import successful"
