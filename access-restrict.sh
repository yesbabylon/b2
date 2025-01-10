#!/bin/bash

# Fichier : restrict-access.sh
# Description : Configure iptables pour limiter l'accès INPUT à HTTP(S) sur l'IP failover.

# Obtenir l'IP associée à veth0
IP_ADDRESS=$(ip -4 addr show dev veth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')

# Vérifier si une IP est trouvée
if [ -z "$IP_ADDRESS" ]; then
    echo "Error: No IP address found on veth0."
    exit 1
fi

echo "Configuring iptables for IP: $IP_ADDRESS"

# Ajouter les règles iptables
iptables -A INPUT -d "$IP_ADDRESS" -j DROP
iptables -I INPUT -d "$IP_ADDRESS" -p tcp --dport 80 -j ACCEPT
iptables -I INPUT -d "$IP_ADDRESS" -p tcp --dport 443 -j ACCEPT

echo "iptables rules applied. Run 'sudo iptables -S' to check."