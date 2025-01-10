#!/bin/bash

# Make sure fail2ban starts on boot
systemctl enable fail2ban

# Restart fail2ban service
systemctl restart fail2ban
