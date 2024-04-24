# B2 repository purpose
Welcome to our repository for initializing instances using the eQual framework! This repository provides scripts and configurations designed to automate the setup process of essential services on Linux servers. 

Whether you're a developer exploring the capabilities of eQual or a business owner seeking efficient server deployment solutions, our scripts simplify the installation and configuration of various tools and services. 

While eQual is the heart of our framework, powering open-source solutions for all, we also offer tailored instance setup services for customers who prefer a more hands-off approach. 

Our experienced team ensures that your servers are configured optimally, allowing you to focus on your core business activities. 

Explore the contents of the repository, contribute to the eQual framework, or reach out to us to learn more about our tailored instance setup services.

Thank you for considering our solutions!

## Important Note
The B2 repository should be placed in the ``/root`` folder of your server.

## Scripts explanation

1. ``install.sh:`` Designed as the foundational script, install.sh automates the setup process for essential Linux server services. From configuring Apache utilities to managing PHP CLI and FTP services, this script ensures a smooth and efficient initial deployment.

   Whether you're a developer experimenting with the eQual framework or a business owner preparing for production, ``install.sh`` streamlines the setup phase, saving time and effort.

2. ``equal/init.bash:`` Building upon the base established by install.sh, eQual/init.bash enriches the server environment with advanced functionalities.
  
   By invoking this script, users can seamlessly initialize the eQualFramework while integrating additional components such as YesBabylon's Symbiose and eQualPress WordPress solutions.

   This comprehensive approach transforms servers into robust hosting and development platforms, offering versatility for various requirements. From web hosting to content management, equal/init.bash empowers users to leverage the full potential of the eQual framework, ensuring a cohesive and efficient setup process.

### ``install.sh``

This script automates the setup process for various services on a Linux server. Below is a breakdown of the tasks it performs:

#### Prerequisite

- This script must be executed with root privileges.

#### Progress tasks

1. **Stop and uninstall Postfix:**
   - Stops the Postfix service if it's running.
   - Uninstalls Postfix.

2. **Update aptitude cache:**
   - Ensures that the aptitude package manager cache is up-to-date.

3. **Set timezone to UTC:**
   - Configures the server timezone to UTC.

4. **Allow using domains as usernames:**
   - Modifies the adduser configuration to allow using domains as usernames.

5. **Install Apache utilities, vnstat, PHP CLI, and FTP service:**
   - Installs Apache utilities (htpasswd), vnstat (bandwidth monitoring tool), PHP CLI, and vsftpd (FTP service).

6. **Configure FTP service:**
   - Customizes the vsftpd configuration.

7. **Restart FTP service:**
   - Restarts the vsftpd service to apply the configuration changes.

8. **Install Fail2Ban:**
   - Installs and configures Fail2Ban for intrusion prevention.

9. **Configure logrotate for Nginx:**
   - Adds logrotate directives for Nginx log rotation.

10. **Install Docker:**
    - Installs Docker CE and Docker CLI.

11. **Install Docker-Compose:**
    - Installs Docker-Compose for managing multi-container Docker applications.

12. **Prepare directory structure:**
    - Copies necessary directories and scripts to their respective locations.

13. **Create 'odoo' user:**
    - Creates a user named 'odoo' without a home directory, login, or prompt.

14. **Set scripts as executable:**
    - Makes various scripts executable.

15. **Create proxy network and volume for Portainer:**
    - Creates a Docker network named 'proxynet' and a Docker volume named 'portainer_data'.

16. **Build docked-nginx image:**
    - Builds the 'docked-nginx' Docker image.

17. **Start reverse proxy and Let's Encrypt companion:**
    - Starts the reverse proxy and Let's Encrypt companion services using Docker Compose.

18. **Add maintenance page and custom Nginx configuration:**
    - Copies a maintenance page and custom Nginx configuration.

19. **Force Nginx to reload configuration:**
    - Reloads Nginx to apply the new configuration.

20. **Edit account parameters and run account creation script:**
    - Edits account parameters and runs the account creation script.

21. **Start Portainer:**
    - Starts the Portainer service.

#### Usage

Execute the script with root privileges.
```bash
./install.sh
```

### ``equal/init.bash``
- Link: [init.bash](https://github.com/yesbabylon/b2/blob/master/equal/init.bash)

#### Requirements
- Ensure that the ``.env`` file is properly configured before executing the script.

#### Usage

To use the `init.bash` script, follow these steps:

1. Ensure that Git, Docker, and `head` are installed on your system.
2. Create a `.env` file with the necessary environment variables. Refer to the [.env](#env-file) section for details.
3. Execute the `init.bash` script with optional arguments.

#### Optional arguments

| Short Flag | Long Flag           | Description      |
|:----------:|---------------------|------------------|
| `-w`       | `--with_wp`         | Install WordPress|
| `-s`       | `--with_sb`         | Install Symbiose |

#### Script Progress

The `init.bash` script progresses through the following steps:

1. **Checking Dependencies:** Verifies essential dependencies like Git, Docker, and `head`. Exits with an error message if any dependency is missing.
2. **Checking for .env File:** Verifies the existence of the `.env` file. Exits with instructions to create it if missing.
3. **Generating MD5 Hash:** Generates an MD5 hash using a random string for the `CIPHER_KEY` in the `.env` file.
4. **Updating .env File:** Updates the `CIPHER_KEY` value in the `.env` file with the generated MD5 hash.
5. **Loading Environment Variables:** Loads environment variables from the `.env` file.
6. **Creating User:** Creates a new user based on provided `USERNAME` and `PASSWORD` from the `.env` file.
7. **Creating Directories:** Creates directories for backup, replication, and user account purposes.
8. **Setting Permissions:** Applies various permissions and settings to directories and user account.
9. **Calling Additional Scripts:**
   - **equal.setup.bash:** Sets up eQualFramework components and configurations.
   - **symbiose.setup.bash:** Installs the Symbiose component if `--with_sb` or `-s` flag is provided.
   - **eQualPress/equalpress/install.sh:** Installs eQualPress WordPress if `--with_wp` or `-w` flag is provided.
  
For further information about these 3 scripts, please refer to the repository or respective folder, A ``README.md`` file is present for more informations about what it does.
- [equal.setup.bash](https://github.com/yesbabylon/b2/blob/master/equal/equal.setup.bash)
- [symbiose.setup.bash](https://github.com/yesbabylon/b2/blob/master/equal/symbiose.setup.bash)
- [equalpress setup script ( ``install.sh`` )](https://github.com/eQualPress/equalpress/blob/main/install.sh)

### `.env` file:
```env
# Customer directoy
USERNAME=test.yb.run

# Applications credentials
APP_USERNAME=root
APP_PASSWORD=test

# CIPHER KEY for eQual config encryption safety
CIPHER_KEY=xxxxxxxxxxxxxx

#Nginx configuration
HTTPS_REDIRECT=noredirect

# VARIABLES BELOW ARE REQUIRED ONLY FOR EQUALPRESS SETUP
# Wordpress version
WP_VERSION=6.4

#Wordpress admin email
WP_EMAIL=root@equal.local

# WordPress site title
WP_TITLE=eQualpress
```
