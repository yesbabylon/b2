#!/bin/bash

# File: host-public_ip_firewall-disable.sh
# Description: Removes iptables rules configured for the failover IP.

# Get the IPs associated with veth0
PUBLIC_IPS=$(ip -4 addr show dev veth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')

# Check if any IP is found
if [ -z "$PUBLIC_IPS" ]; then
    echo "Error: No IP addresses found on veth0."
    exit 1
fi

for IP in $PUBLIC_IPS; do
    echo "Removing iptables rules for IP: $IP"

    # Remove iptables rules
    iptables -D INPUT -d "$IP" -p tcp --dport 80 -j ACCEPT
    iptables -D INPUT -d "$IP" -p tcp --dport 443 -j ACCEPT
    iptables -D INPUT -d "$IP" -j DROP
done

echo "iptables rules removed. Run 'sudo iptables -S' to check."
