#!/bin/bash

# File: public_ip_firewall-disable.sh
# Description: Removes iptables rules configured for the failover IP.

# Get the IP associated with veth0
IP_ADDRESS=$(ip -4 addr show dev veth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')

# Check if an IP is found
if [ -z "$IP_ADDRESS" ]; then
    echo "Error: No IP address found on veth0."
    exit 1
fi

echo "Removing iptables rules for IP: $IP_ADDRESS"

# Remove iptables rules
iptables -D INPUT -d "$IP_ADDRESS" -p tcp --dport 80 -j ACCEPT
iptables -D INPUT -d "$IP_ADDRESS" -p tcp --dport 443 -j ACCEPT
iptables -D INPUT -d "$IP_ADDRESS" -j DROP

echo "iptables rules removed. Run 'sudo iptables -S' to check."