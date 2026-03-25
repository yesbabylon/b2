#!/bin/bash

# Stop fail2ban service
systemctl stop fail2ban

# Do not starts fail2ban on boot
systemctl disable fail2ban


