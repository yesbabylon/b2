#!/bin/bash
# build a new container in current directory, using host mysql and apache UID and GUI
docker build -t docked-wp --build-arg apache_gid=`cut -d: -f3 < <(getent group www-data)` --build-arg apache_uid=`id -u www-data` .