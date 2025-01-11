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
