# b2


The b2 project combines the configuration of services (Docker, Nginx, Let's Encrypt, Fail2Ban, GPG) and tasks (instance creation, backup, restore, etc.) to form the architecture of a host designed for running containerized eQual instances.

This repository contains scripts to automate the setup of a B2 host and simplify maintenance operations. 

For those wondering about the name "b2", **bītu** (𒂍) means "home" in Akkadian/old Babylonian.

For more information about the eQual framework, visit the [eQual GitHub repository](https://github.com/equalframework/equal) and the [official website](https://equal.run/).



                                     |


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
ADMIN_HOST=admin.local
BACKUP_HOST=backup.local
STATS_HOST=stats.local
PUBLIC_IP=192.168.1.1
GPG_PASSPHRASE=your-passphrase-here
ROOT_PASSWORD=your-root-password
```
**Variables**:

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

## Repository structure



| **Path**                                  | **Description**                                            |
| ----------------------------------------- | ---------------------------------------------------------- |
| `README.md`                               | Main documentation for the project.                        |
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
| │   │   │   ├── `joomla-login.conf`       | Fail2Ban filter for Joomla login attempts.                 |
| │   │   │   ├── `nginx-req-limit.conf`    | Fail2Ban filter for NGINX request limits.                  |
| │   │   │   ├── `ps-login.conf`           | Fail2Ban filter for PrestaShop login attempts.             |
| │   │   │   └── `wp-login.conf`           | Fail2Ban filter for WordPress login attempts.              |
| │   │   └── `jail.local`                  | Local configuration for Fail2Ban jails.                    |
| │   ├── `logrotate.d/nginx`               | Log rotation configuration for NGINX.                      |
| │   └── `vsftpd.conf`                     | Configuration for the VSFTPD service.                      |
| ├── `instance/create/`                    | Scripts and templates for creating instances.              |
| │   ├── `create.bash`                     | Main script to create an instance.                         |
| │   ├── `init-equal.bash`                 | Initialization script for Equal.                           |
| │   └── `template/`                       | Templates for instance configurations.                     |
| │       ├── `docker-compose.yml`          | Docker Compose template for instances.                     |
| │       ├── `mysql.cnf`                   | MySQL configuration template.                              |
| │       └── `php.ini`                     | PHP configuration template.                                |
| ├── `key-gen.conf`                        | Configuration for key generation.                          |
| ├── `nginx.conf`                          | Main NGINX configuration file.                             |
| ├── `ssh-login`                           | Configuration or script for SSH login setup.               |
| └── `vhost.d/default`                     | Default virtual host configuration.                        |
| `doc/`                                    | Documentation files directory.                             |
| └── `api.md`                              | API documentation.                                         |
| `install.sh`                              | Installation script for the project.                       |
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
| `utils/`                                  | Utility scripts directory.                                 |
| ├── `b2_listener-disable.sh`              | Script to disable the b2-listener service.                 |
| ├── `b2_listener-enable.sh`               | Script to enable the b2-listener service.                  |
| ├── `fail2ban-disable.sh`                 | Script to disable Fail2Ban.                                |
| ├── `fail2ban-enable.sh`                  | Script to enable Fail2Ban.                                 |
| ├── `public_ip_firewall-disable.sh`       | Script to disable the public IP firewall (IP tables).      |
| └── `public_ip_firewall-enable.sh`        | Script to enable the public IP firewall (IP tables).       |
