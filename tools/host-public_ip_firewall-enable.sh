#!/bin/bash

# File: host-public_ip_firewall-enable.sh
# Description: Configures iptables to limit INPUT access to HTTP(S) on the failover IP.

# Get the IPs associated with veth0
PUBLIC_IPS=$(ip -4 addr show dev veth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')

# Check if any IP is found
if [ -z "$PUBLIC_IPS" ]; then
    echo "Error: No IP addresses found on veth0."
    exit 1
fi

for IP in $PUBLIC_IPS; do
    echo "Configuring iptables for IP: $IP"

    # Add iptables rules
    iptables -A INPUT -d "$IP" -j DROP
    iptables -I INPUT -d "$IP" -p tcp --dport 80 -j ACCEPT
    iptables -I INPUT -d "$IP" -p tcp --dport 443 -j ACCEPT
done

echo "iptables rules applied. Run 'sudo iptables -S' to check."
