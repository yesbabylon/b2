#!/bin/bash
# build a new container in current directory, using host odoo UID and GUI
docker build -t docked-odoo --build-arg odoo_gid=`cut -d: -f3 < <(getent group odoo)` --build-arg odoo_uid=`id -u odoo` .
