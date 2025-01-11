#!/bin/bash

# Stop fail2ban service
systemctl stop b2-listener.service

# Do not starts fail2ban on boot
systemctl mask b2-listener.service
