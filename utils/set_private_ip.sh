#!/bin/bash

# Check if both arguments are provided
if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <PRIVATE_IP> <MAC_ADDRESS>"
    exit 1
fi

IP_PRIVATE=$1
MAC_ADDRESS=$2

# Retrieve IPv6 address and MAC address of ens3
ENS3_IPV6=$(ip -6 addr show ens3 | grep 'inet6 ' | awk '{print $2}' | head -n 1)
ENS3_MAC=$(cat /sys/class/net/ens3/address)
ENS3_DNS=$(resolvectl dns ens3 | awk '{print $4}')
ENS3_GATEWAY=$(ip -6 route show default | awk '/default/ {print $3}')

# Check if values were retrieved successfully
if [ -z "$ENS3_IPV6" ] || [ -z "$ENS3_MAC" ] || [ -z "$ENS3_DNS" ] || [ -z "$ENS3_GATEWAY" ]; then
    echo "Error: Failed to retrieve IPv6, MAC address, DNS, or Gateway for ens3"
    exit 1
fi

# Netplan configuration file path
NETPLAN_FILE="/etc/netplan/50-cloud-init.yaml"

# Generate Netplan configuration file
cat <<EOL > $NETPLAN_FILE
network:
    version: 2
    ethernets:
        ens3:
            accept-ra: false
            addresses:
            - $ENS3_IPV6/56
            dhcp4: true
            match:
                macaddress: $ENS3_MAC
            mtu: 1500
            nameservers:
                addresses:
                - $ENS3_DNS
                search: []
            routes:
            -   to: ::/0
                via: $ENS3_GATEWAY
            set-name: ens3
        
        ens4:
            match:
                macaddress: "$MAC_ADDRESS"
            addresses:
            - "$IP_PRIVATE/16"
            nameservers:
                addresses:
                - 0.0.0.0
            set-name: "ens4"
            mtu: 1500
EOL

# Apply Netplan configuration
netplan apply

echo "Netplan configuration updated: $NETPLAN_FILE"
