#!/bin/bash

# File: public_ip_firewall-enable.sh
# Description: Configures iptables to limit INPUT access to HTTP(S) on the failover IP.

# Get the IP associated with veth0
IP_ADDRESS=$(ip -4 addr show dev veth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')

# Check if an IP is found
if [ -z "$IP_ADDRESS" ]; then
    echo "Error: No IP address found on veth0."
    exit 1
fi

echo "Configuring iptables for IP: $IP_ADDRESS"

# Add iptables rules
iptables -A INPUT -d "$IP_ADDRESS" -j DROP
iptables -I INPUT -d "$IP_ADDRESS" -p tcp --dport 80 -j ACCEPT
iptables -I INPUT -d "$IP_ADDRESS" -p tcp --dport 443 -j ACCEPT

echo "iptables rules applied. Run 'sudo iptables -S' to check."