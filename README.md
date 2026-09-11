# b2


The b2 project combines the configuration of services (Docker, Nginx, Let's Encrypt, Fail2Ban, GPG) and tasks (instance creation, backup, restore, etc.) to form the architecture of a host designed for running containerized eQual instances.

This repository contains scripts to automate the setup of a B2 host and simplify maintenance operations. 

For those wondering about the name "b2", **bītu** (𒂍) means "home" in Akkadian/old Babylonian.

For more information about the eQual framework, visit the [eQual GitHub repository](https://github.com/equalframework/equal) and the [official website](https://equal.run/).




## Install

The `./install.sh` script :
  - **setup essential services** on an Ubuntu server
  - **starts a listener API** that allows to smoothly manage the host and its instances through a [ReST API](doc/api.md).

### Prerequisite
* The b2 repository must be placed in the `/root` folder of the server.
* The `install.sh` script must be executed with **root privileges**.

### Steps performed by the script

1. Checks that script run on correct directory and checks required args
2. Creates .env file from .env.example and add/update GPG_* with command given args
3. Does base server configurations and installs base services that are needed (vnstat, php-cli, apache2-utils, vsftpd)
4. Creates the gpg keys for backup encryption
5. Installs Docker for instances
6. Installs cron and configure it, it'll start `./cron.php` every minute
7. Installs fail2ban
8. Installs [API](./doc/api.md) service that will listen for requests on port :8000
9. Installs Portainer that will listen on :9000


### Usage

```bash
./install.sh
```

The script expects a `.env` file with the following values (exported as environment variables):

```env
HOSTNAME=b2-host-01
ADMIN_HOST=admin.local
BACKUP_HOST=backup.local
STATS_HOST=stats.local
PUBLIC_IP=192.168.1.1
GPG_PASSPHRASE=your-passphrase-here
ROOT_PASSWORD=your-root-password
```
**Variables**:

- **`HOSTNAME`**  
  Identifier/name of the host (required).

- **`ADMIN_HOST`**  
  Hostname or IP address of the admin host API endpoint (required).

- **`BACKUP_HOST`**  
  Hostname or IP address of the backup host API endpoint (required).

- **`STATS_HOST`**  
  Hostname or IP address of the stats host API endpoint (required).

- **`GPG_PASSPHRASE`**  
  Passphrase for the PGP key (required).

- **`PUBLIC_IP`**  
  Fail-over / Public IPv4 address (required).

- **`ROOT_PASSWORD`**  
  Password for the root account of the host (required).

**Note:** `GPG_PASSPHRASE` and `ROOT_PASSWORD` are automatically removed from the `.env` file after the successful execution of the `install.sh` script.

## Instance secrets

The `secrets/` directory is the host-local store for secrets required while initializing an instance. Secret files are named after the corresponding `INSTANCE_TYPE`. For example, FMT secrets are stored in:

```text
/root/b2/secrets/fmt.json
```

The directory itself is present in the repository through `secrets/.gitkeep`, but every other file in it is ignored by Git. Secret files must be created manually on the target host after B2 has been installed.

**Never add a secret file, real credentials, or an encoded copy of them directly to the repository.** Do not bypass the ignore rule with `git add -f`, and do not send these values through the instance creation API or command-line arguments. Base64 encoding does not protect a secret.

Create and protect the FMT secret file on the target host with:

```bash
mkdir -p /root/b2/secrets
chown root:root /root/b2/secrets
chmod 700 /root/b2/secrets

touch /root/b2/secrets/fmt.json
chown root:root /root/b2/secrets/fmt.json
chmod 600 /root/b2/secrets/fmt.json
```

The file must contain a JSON object whose values are strings. For example, using placeholder values only:

```json
{
  "GOOGLE_GMAIL_CLIENT_ID": "replace-on-target-host",
  "GOOGLE_GMAIL_CLIENT_SECRET": "replace-on-target-host",
  "MS_TENANT_ID": "replace-on-target-host",
  "MS_OUTLOOK_CLIENT_ID": "replace-on-target-host",
  "MS_OUTLOOK_CLIENT_SECRET": "replace-on-target-host"
}
```

During FMT initialization, `conf/instance/fmt/prepare.php` optionally reads `/root/b2/secrets/fmt.json`. Only explicitly allowed FMT keys are injected into the generated instance configuration; unknown keys are ignored. When the file is absent, initialization continues without host-provided FMT secrets. When it exists but is unreadable, contains invalid JSON, or contains a non-string value, initialization stops with an error.

## Repository structure



| **Path**                                  | **Description**                                            |
| ----------------------------------------- | ---------------------------------------------------------- |
| `install.sh`                              | Installation script for the project (see Install section). |
| `README.md`                               | Main documentation for the project (this file).            |
| `secrets/`                                | Host-local instance secret files; only `.gitkeep` is committed. |
| `conf/`                                   | Directory containing configuration files.                  |
| ├── `b2-listener.service`                 | Systemd service file for the `b2-listener`.                |
| ├── `default.crt`                         | Default SSL certificate.                                   |
| ├── `default.key`                         | Default private SSL key.                                   |
| ├── `dhparam.pem`                         | Diffie-Hellman parameters for SSL.                         |
| ├── `docker/`                             | Docker-related configuration files.                        |
| │   ├── `images/docked-nginx/`            | Configuration for the NGINX Docker image.                  |
| │   │   ├── `Dockerfile`                  | Dockerfile to build the NGINX image.                       |
| │   │   ├── `build.sh`                    | Script to build the NGINX Docker image.                    |
| │   │   ├── `maintenance.html`            | Maintenance page for NGINX.                                |
| │   │   └── `nginx.tmpl`                  | Template for NGINX configuration.                          |
| │   ├── `nginx-proxy/docker-compose.yml`  | Docker Compose configuration for the NGINX proxy.          |
| │   ├── `portainer.service`               | Systemd service file for Portainer.                        |
| │   └── `portainer_start.sh`              | Script to start Portainer.                                 |
| ├── `etc/`                                | Additional system configuration files.                     |
| │   ├── `adduser.conf`                    | Configuration for the `adduser` command.                   |
| │   ├── `fail2ban/`                       | Fail2Ban configuration directory.                          |
| │   │   ├── `action.d/docker-action.conf` | Custom actions for Fail2Ban with Docker.                   |
| │   │   ├── `filter.d/`                   | Fail2Ban filters directory.                                |
| │   │   │   ├── `eq-login.conf`           | Fail2Ban filter for Equal login attempts.                  |
| │   │   │   ├── `nginx-req-limit.conf`    | Fail2Ban filter for NGINX request limits.                  |
| │   │   │   └── `wp-login.conf`           | Fail2Ban filter for WordPress login attempts.              |
| │   │   └── `jail.local`                  | Local configuration for Fail2Ban jails.                    |
| │   ├── `logrotate.d/nginx`               | Log rotation config for NGINX.                             |
| │   └── `vsftpd.conf`                     | Configuration for the VSFTPD service.                      |
| ├── `instance/`                    | Per-type assets for instance provisioning.                 |
| │   ├── `equal/`                          | `equal` assets (compose/config/scripts + `init.sh`).       |
| │   ├── `wordpress/`                      | `wordpress` assets (compose/config/scripts + `init.sh`).   |
| │   ├── `equalpress/`                     | `equalpress` assets (compose/config/scripts + `init.sh`).  |
| │   └── `symbiose/`                       | `symbiose` assets (compose/config/scripts + `init.sh`).    |
| ├── `key-gen.conf`                        | Configuration for key generation.                          |
| ├── `nginx.conf`                          | Main NGINX configuration file.                             |
| ├── `ssh-login`                           | Configuration or script for SSH login setup.               |
| └── `vhost.d/default`                     | Default virtual host configuration.                        |
| `doc/`                                    | Documentation files directory.                             |
| ├── `api.md`                              | API documentation.                                         |
| └── `cli-memo.md`                         | CLI memo for `run.sh` routes/actions and examples.         |
| `keyring/`                                | Directory containing cryptographic keys.                   |
| ├── `gpg-private-key.pgp`                 | Private GPG key.                                           |
| └── `gpg-public-key.pgp`                  | Public GPG key.                                            |
| `logs/`                                   | Directory for logs.                                        |
| ├── `b2-listener-error.log`               | Log file for errors in the b2-listener service (stderr).   |
| └── `b2-listener-output.log`              | Log file for output from the b2-listener service (stdout). |
| `src/`                                    | Main source code directory.                                |
| ├── `boot.lib.php`                        | Bootstrap library for the PHP scripts.                     |
| ├── `controllers/`                        | Controllers for handling application logic.                |
| │   ├── `instance/`                       | Controllers related to instances management.               |
| │   │   ├── `backup.php`                  | Controller to handle instance backups.                     |
| │   │   ├── `create.php`                  | Controller to create instances.                            |
| │   │   ├── `delete.php`                  | Controller to delete instances.                            |
| │   │   └── `status.php`                  | Controller to get the status of instances.                 |
| ├── `helpers/`                            | Helper functions for various operations.                   |
| │   ├── `backup.php`                      | Helper for managing backups.                               |
| │   ├── `cron-handler.php`                | Helper to handle cron jobs.                                |
| │   └── `request-handler.php`             | Helper for handling HTTP requests.                         |
| ├── `listener.php`                        | Main script for the b2-listener service.                   |
| ├── `run.php`                             | Entry point for running the application.                   |
| └── `send.php`                            | Script to send requests or notifications.                  |
| `scripts/`                                | Utility scripts directory.                                 |
| ├── `host-b2_listener-disable.sh`         | Disable the b2-listener service.                           |
| ├── `host-b2_listener-enable.sh`          | Enable the b2-listener service.                            |
| ├── `host-docker_images-export.sh`        | Export Docker images to an archive.                        |
| ├── `host-docker_images-import.sh`        | Import Docker images from an archive.                      |
| ├── `host-fail2ban-disable.sh`            | Disable the Fail2Ban service.                              |
| ├── `host-fail2ban-enable.sh`             | Enable the Fail2Ban service.                               |
| ├── `host-hostname-set.sh`                | Set the host name.                                         |
| ├── `host-private_ip-set.sh`              | Configure the host private IP address.                     |
| ├── `host-public_ip-add.sh`               | Add a public IP address to the host.                        |
| ├── `host-public_ip-remove.sh`            | Remove a public IP address from the host.                   |
| ├── `host-public_ip_firewall-disable.sh`  | Disable firewall rules for public IP addresses.            |
| ├── `host-public_ip_firewall-enable.sh`   | Enable firewall rules for public IP addresses.             |
| ├── `host-ssl_certificates-export.sh`     | Export instance certificates from the host.                |
| ├── `host-ssl_certificates-import.sh`     | Import instance certificates into the host.                |
| ├── `instance-eq_logs-rotate.sh`          | Rotate the eQual logs for an instance.                      |
| ├── `instance-eq_version-get.sh`          | Return the eQual version of an instance.                    |
| ├── `instance-php_version-get.sh`         | Return the PHP version of an instance.                      |
| └── `instance-wp_version-get.sh`          | Return the WordPress version of an instance.                |



## Examples

Examples of commands to be run under the `/src` folder:

* Create a backup for the instance "yesbabylon.com"
```
php run.php --route=instance/backup --instance=yesbabylon.com
```

* Export the backup "2025011158175" for instance "yesbabylon.com" to the backup host
```
php run.php --route=instance/export-backup --instance=yesbabylon.com --backup_id=2025011158175
```
