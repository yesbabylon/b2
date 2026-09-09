#!/bin/bash

SERVICE_FILE="/etc/systemd/system/b2-listener.service"

if [[ ! -f "$SERVICE_FILE" ]]; then
    ln -s /root/b2/conf/b2-listener.service "$SERVICE_FILE"
fi

# Make sure b2-listener starts on boot
systemctl enable b2-listener.service

# Restart the b2-listener service
systemctl restart b2-listener.service
