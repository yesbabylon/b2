#!/bin/bash
# Usage:
# ./export-images.sh output.tar.gz image1:tag image2:tag ...
#
# Example:
# ./export-images.sh equal-2.0.0-snapshot.tar.gz mysql:5.7.44 phpmyadmin/phpmyadmin:5.2.1 equalframework/equal:2.0.0

OUTPUT=$1
shift
if [ -z "$OUTPUT" ] || [ $# -eq 0 ]; then
  echo "Usage: $0 output.tar.gz image1:tag image2:tag ..."
  exit 1
fi
echo "Exporting images: $@"
echo "Output file: $OUTPUT"
# Export + compression en une seule passe
docker save "$@" | gzip > "$OUTPUT"
if [ $? -eq 0 ]; then
  echo "Export successful"
else
  echo "Export failed"
  exit 1
fi
