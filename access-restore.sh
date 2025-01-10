#!/bin/bash

# Fichier : remove-restrictions.sh
# Description : Supprime les règles iptables configurées pour l'IP failover.

# Obtenir l'IP associée à veth0
IP_ADDRESS=$(ip -4 addr show dev veth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')

# Vérifier si une IP est trouvée
if [ -z "$IP_ADDRESS" ]; then
    echo "Error: No IP address found on veth0."
    exit 1
fi

echo "Removing iptables rules for IP: $IP_ADDRESS"

# Supprimer les règles iptables
iptables -D INPUT -d "$IP_ADDRESS" -p tcp --dport 80 -j ACCEPT
iptables -D INPUT -d "$IP_ADDRESS" -p tcp --dport 443 -j ACCEPT
iptables -D INPUT -d "$IP_ADDRESS" -j DROP

echo "iptables rules removed. Run 'sudo iptables -S' to check."