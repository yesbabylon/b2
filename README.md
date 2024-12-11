# b2

Welcome to our repository that allows you to easily initialize eQual instances on your Ubuntu server!
This repository provides scripts and configurations designed to automate the setup process of an eQual ecosystem.

The `./install.sh` script:
  - **setup essential services** on an Ubuntu server
  - **starts an API** that allows to smoothly manage the host and its instances

Whether you're a developer exploring the capabilities of eQual or a business owner seeking efficient server deployment
solutions, our scripts simplify the installation and configuration of various tools and services.

While eQual is the heart of our framework, powering open-source solutions for all, we also offer tailored instance setup
services for customers who prefer a more hands-off approach.

Our experienced team ensures that your servers are configured optimally, allowing you to focus on your core business
activities.

Explore the contents of the repository, contribute to the eQual framework, or reach out to us to learn more about our
tailored instance setup services.

For more information about the eQual framework,
visit the [eQual GitHub repository](https://github.com/equalframework/equal) and
our [official website](https://equal.run/).

Thank you for considering our solutions!

## Important Note

The b2 repository must be placed in the `/root` folder of your server.

## Install

Designed as the foundational script, `./install.sh` automates the setup process for essential Ubuntu server services.
From configuring Apache utilities to managing PHP CLI, firewall, linux user management, and FTP services, this script ensures a smooth and efficient initial deployment.
Whether you're a developer experimenting with the eQual framework or a business owner preparing for production, this script streamlines the setup phase, saving time and effort.

This script automates the setup process for various services on an Ubuntu server.  

Below is a breakdown of the tasks it performs.

### Prerequisite

This script must be executed with **root privileges**.

### Script steps

1. Checks that script run on correct directory and checks required args
2. Creates .env file from .env.example and add/update GPG_* with command given args
3. Installs base services that are needed
4. Creates the gpg keys for backup encryption
5. Installs Docker for instances
6. Installs cron and configure it, it'll start cron.php every minute
7. Installs fail2ban
8. Installs API service that will listen for requests on port :8000
9. Installs Portainer that will listen on :9000

### Usage

```bash
./install.sh --gpg_name b2 --gpg_email b2@your-company.com --gpg_expiry_date 0 --gpg_passphrase thepassword1234
```

**Notes**:
  - You must **execute** the installation script with **root privileges**.
  - The **gpg arguments** are needed  to **encrypt the backups** of the instances.
