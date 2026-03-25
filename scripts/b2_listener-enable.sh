#!/bin/bash

SERVICE_FILE="/etc/systemd/system/b2-listener.service"

if [[ ! -f "$SERVICE_FILE" ]]; then
    ln -s /root/b2/conf/b2-listener.service "$SERVICE_FILE"
fi

# Make sure fail2ban starts on boot
systemctl enable b2-listener.service

# Restart fail2ban service
systemctl restart b2-listener.service
