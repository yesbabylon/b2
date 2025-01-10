#!/bin/bash

# Make sure fail2ban starts on boot
systemctl enable b2-listener.service

# Restart fail2ban service
systemctl restart b2-listener.service
