#!/bin/bash

INSTALL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Force the Fail2ban configuration update
cp "$INSTALL_DIR"/conf/etc/fail2ban/jail.local /etc/fail2ban/jail.local
cp "$INSTALL_DIR"/conf/etc/fail2ban/action.d/* /etc/fail2ban/action.d/
cp "$INSTALL_DIR"/conf/etc/fail2ban/filter.d/* /etc/fail2ban/filter.d/

# Make sure watched logs are present (Fail2ban does not start jails targeting a missing file)
touch /etc/fail2ban/emptylog
touch /var/log/nginx/access.log

# Make sure fail2ban starts on boot
systemctl enable fail2ban

# Restart fail2ban service
systemctl restart fail2ban
