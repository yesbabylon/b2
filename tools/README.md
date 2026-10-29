# B2 operator tools

This directory contains command-line tools for administering a B2 host and its
instances. The files are executable Bash scripts, but should be treated as an
operational interface: inspect their usage and effects before invoking them.

Run commands from the repository root so examples and relative paths remain
predictable:

```bash
sudo ./tools/host-swap-enable.sh 2G
sudo ./tools/instance-wp-lock.sh example.org
sudo ./tools/instance-wp-lock.sh example.org full
```

`USERNAME` normally identifies both the Linux account and the instance domain,
with its files stored under `/home/USERNAME`. Tools marked **root** change host
state or protected files and should be run with `sudo`. Tools marked **Docker**
require access to the Docker daemon.

## Host tools

| Tool | Arguments | Purpose | Access |
| --- | --- | --- | --- |
| `host-b2_listener-disable.sh` | none | Stop and disable the B2 listener service. | root |
| `host-b2_listener-enable.sh` | none | Install, enable and restart the B2 listener on `PRIVATE_IP:8000`. | root |
| `host-docker_images-export.sh` | `OUTPUT IMAGE...` | Export and gzip one or more Docker images. | Docker |
| `host-docker_images-import.sh` | `ARCHIVE` | Import Docker images from a tar or gzip archive. | Docker |
| `host-docker_images-unused.sh` | none | List Docker images unused by any container. | Docker/read |
| `host-fail2ban-diagnoseip.sh` | `IP [full]` | Diagnose which Fail2Ban filters match an IPv4 address. | root/read |
| `host-fail2ban-disable.sh` | none | Stop and disable Fail2Ban. | root |
| `host-fail2ban-enable.sh` | none | Deploy repository configuration and enable Fail2Ban. | root |
| `host-fail2ban-unbanip.sh` | `IP_PATTERN` | Unban an IPv4 address or wildcard pattern. | root |
| `host-hostname-set.sh` | `HOSTNAME` | Change the host name and update `/etc/hosts`. | root |
| `host-nginx-reload.sh` | none | Validate and reload NGINX in `nginx-proxy`. | Docker |
| `host-network_private_ip-set.sh` | `PRIVATE_IP MAC_ADDRESS` | Replace the host Netplan configuration for its private network. | root |
| `host-network_public_ip-add.sh` | `PUBLIC_IP` | Add a public IPv4 address to the failover Netplan configuration. | root |
| `host-network_public_ip-remove.sh` | `PUBLIC_IP` | Remove a public IPv4 address from the failover Netplan configuration. | root |
| `host-network_public_ip-set.sh` | `PUBLIC_IP` | Set the first public IPv4 address, refusing to replace an existing address. | root |
| `host-network_public_ip_firewall-disable.sh` | none | Remove failover-IP firewall rules from `iptables`. | root |
| `host-network_public_ip_firewall-enable.sh` | none | Restrict failover-IP input traffic to HTTP and HTTPS. | root |
| `host-ssl_certificates-export.sh` | `USERNAME [OUTPUT] [CERT_DIR]` | Export one instance's NGINX certificates. | root/read |
| `host-ssl_certificates-import.sh` | `USERNAME ARCHIVE [CERT_DIR]` | Back up and replace one instance's NGINX certificates. | root, Docker |
| `host-ssl_certificates-renew.sh` | none | Trigger certificate renewal in `letsencrypt-companion`. | Docker |
| `host-swap-disable.sh` | none | Disable `/swapfile` and remove its persistent entry. | root |
| `host-swap-enable.sh` | `[SIZE]` | Create or enable persistent swap; the default size is `2G`. | root |
| `host-swap-resize.sh` | `SIZE` | Resize persistent swap with rollback on failure. | root |

## Instance tools

| Tool | Arguments | Purpose | Access |
| --- | --- | --- | --- |
| `instance-eq-logrotate.sh` | `USERNAME [MAX_MB] [KEEP]` | Cap `equal.log` and retained archives near 100 MiB by default; keep five archives. | filesystem write |
| `instance-eq-version.sh` | `USERNAME` | Print the installed eQual version. | read |
| `instance-php-version.sh` | `USERNAME` | Print the PHP version from the instance container. | Docker/read |
| `instance-wp-lock.sh` | `USERNAME [full]` | Remove write permission from WordPress; uploads remain writable unless `full` is used. | root |
| `instance-wp-unlock.sh` | `USERNAME` | Restore owner and `www-data` permissions for WordPress maintenance. | root |
| `instance-wp-version.sh` | `USERNAME` | Print the installed WordPress version. | read |

## Safety notes

- There is currently no global `--dry-run` option.
- A successful syntax check does not make a command safe for the current host.
- Network, firewall, certificate, swap and permission tools can interrupt live
  services or applications.
- WordPress locking prevents updates and other writes until the matching
  unlock tool is run. By default, uploads remain writable; use the `full` mode
  to prevent uploads as well.
- When an agent proposes a mutating invocation, it should state the exact
  command, target and expected effect before execution.

For implementation details and exact validation rules, read the script itself.
