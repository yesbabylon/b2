# B2 repository purpose

Welcome to our repository for initializing instances using the eQual framework!

This repository provides scripts and configurations designed to automate the setup process of eQual ecosystem.
These scripts setup essential services on Linux servers and setup listener server for automatically handling some
instance process automatically.

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

The B2 repository should be placed in the ``/root`` folder of your server.
For further information about these three scripts, please refer to the repository or respective folder, the other
scripts for instance initialization are:
are in the [equal folder](https://github.com/yesbabylon/b2/tree/master/equal) of this repository.

## Scripts explanation

1. ``install.sh``: Designed as the foundational script, ``install.sh`` automates the setup process for essential Linux
   server services.

   From configuring Apache utilities to managing PHP CLI, firewall, linux user management, and FTP
   services, this script ensures a smooth and efficient initial deployment.

   Whether you're a developer experimenting with the eQual framework or a business owner preparing for
   production, ``install.sh`` streamlines the setup phase, saving time and effort.  
2. ``equal/init.bash``: Building upon the base established by ``install.sh``, ``equal/init.bash`` enriches the server
   environment with advanced functionalities.

   By invoking this script, users can seamlessly initialize the eQualFramework while integrating additional components
   such as [YesBabylon Symbiose](https://github.com/yesbabylon/symbiose) and [eQualPress](https://github.com/eQualPress),
   our WordPress solutions.

   This comprehensive approach transforms servers into robust hosting and development platforms, offering versatility
   for various requirements.

   From web hosting to content management, ``equal/init.bash`` empowers users to leverage the full
   potential of the eQual framework, ensuring a cohesive and efficient setup process.

### ``install.sh``

This script automates the setup process for various services on a Linux server.  

Below is a breakdown of the tasks it performs.

#### Prerequisite

- This script must be executed with root privileges.

#### Script Progress

1. **Stop and uninstall Postfix:**
    - Stop the Postfix service if it's running.
    - Uninstalls Postfix.

2. **Update aptitude cache:**
    - Ensures that the aptitude package manager cache is up to date.

3. **Set timezone to UTC:**
    - Configure the server timezone to UTC.

4. **Allow using domains as usernames:**
    - Modifies the adduser configuration to allow using domains as usernames.

5. **Install Apache utilities, vnstat, PHP CLI, and FTP service:**
    - Installs Apache utilities (htpasswd), vnstat (bandwidth monitoring tool), PHP CLI, and vsftpd (FTP service).

6. **Configure FTP service:**
    - Customizes the vsftpd configuration.

7. **Restart FTP service:**
    - Restart the vsftpd service to apply the configuration changes.

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
    - Create a user named ``odoo`` without a home directory, login, or prompt.

14. **Set scripts as executable:**
    - Makes various scripts executable.

15. **Create proxy network and volume for Portainer:**
    - Create a Docker network named ``proxynet`` and a Docker volume named ``portainer_data``.

16. **Build docked-nginx image:**
    - Builds the 'docked-nginx' Docker image.

17. **Start reverse proxy and Let's Encrypt companion:**
    - Start the reverse proxy and Let's Encrypt companion services using Docker Compose.

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