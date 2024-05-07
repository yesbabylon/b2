# Scripts explanation

<!-- TOC -->
* [Scripts explanation](#scripts-explanation)
    * [``equal/init.bash``](#equalinitbash)
      * [Requirements](#requirements)
      * [Usage](#usage)
      * [Optional arguments](#optional-arguments)
      * [Script Progress](#script-progress)
  * [``equal.setup.bash``](#equalsetupbash)
    * [Prerequisites](#prerequisites)
    * [Progress Tasks](#progress-tasks)
  * [``symbiose.setup.bash``](#symbiosesetupbash)
    * [Progress Task](#progress-task)
  * [``prod.sh``](#prodsh)
    * [Usage](#usage-1)
    * [`.env` file:](#env-file)
      <!-- TOC -->

### ``equal/init.bash``

The purpose of the init.sh script is to generate a new account and a related folder under `/home`.

And, in that folder, to create a docker-compose.yml file holding all required configuration for building a consistent docker stack.



Note: in order to consider the resources limitations in the `deploy` section, docker-compose has to be called with the `--compatibility` flag.

`docker-compose --compatibility up -d`



- Link: [``init.bash``](https://github.com/yesbabylon/b2/blob/master/equal/init.bash)

#### Requirements

- Ensure that the [``.env``](#env-file) file is properly configured before executing the script.

#### Usage

To use the `init.bash` script, follow these steps:

1. Ensure that Git, Docker, and `head` are installed on your system.
2. Create a `.env` file with the necessary environment variables. Refer to the [``.env``](#env-file) section for
   details.
3. Execute the `init.bash` script with optional arguments.

#### Optional arguments

| Short Flag | Long Flag   | Description       |
|:----------:|-------------|-------------------|
|    `-w`    | `--with_wp` | Install WordPress |
|    `-s`    | `--with_sb` | Install Symbiose  |

#### Script Progress

The `init.bash` script progresses through the following steps:

1. **Checking Dependencies:** Verifies essential dependencies like Git, Docker, and `head`. Exits with an error message
   if any dependency is missing.
2. **Checking for .env File:** Verifies the existence of the `.env` file.
   Exits with instructions to create it if missing.
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

For further information about these three scripts, please refer to the repository or respective folder,
A ``README.md`` file is present for more information about what it does.

- [equal.setup.bash](https://github.com/yesbabylon/b2/blob/master/equal/equal.setup.bash)

- [symbiose.setup.bash](https://github.com/yesbabylon/b2/blob/master/equal/symbiose.setup.bash)

- [equalpress setup script ( ``install.sh`` )](https://github.com/eQualPress/equalpress/blob/main/install.sh)

    

## ``equal.setup.bash``

This script is responsible for setting up the eQual Framework environment on a server.
It copies the `docker-compose.yml` file from the B2 repository to the user's home directory,
replaces placeholders in the file with computed values,
builds and starts the Docker containers, clones the eQual framework repository,
generates the configuration file, and initializes the eQual Framework database and core package.

### Prerequisites

- Docker and Docker Compose must be installed on the server.
- The B2 repository containing the `docker-compose.yml` file must be available in the `/root` folder.

### Progress Tasks

1. **Get docker-compose.yml file:**
    - Copies the `docker-compose.yml` file from the B2 repository to the user's home directory.

2. **Replace Placeholders for Docker Compose:**
    - Replaces placeholders in the `docker-compose.yml` file with computed values such
      as `EQ_PORT`, `DB_HOSTNAME`, `DB_PORT`, `PMA_HOSTNAME`, and `PMA_PORT`.

3. **Building and Starting the Containers:**
    - Builds and starts the Docker containers defined in the `docker-compose.yml` file.

4. **Clone eQual Framework:**
    - Clones the eQual framework repository from the specified branch (`dev-2.0`).

5. **Generate config/config.json:**
    - Generates the `config.json` file for the eQual Framework with database and application details.

6. **Initialize eQual Framework Database and Core Package:**
    - Initializes the eQual Framework database and imports the core package.

## ``symbiose.setup.bash``

This script initiates the cloning and setup process for the Symbiose tool within the designated environment.

### Progress Task

The script performs the following tasks:

- Clones the Symbiose repository from the ``dev-2.0`` branch.
- Move core and demo packages to the appropriate directory.
- Removes the outdated packages-core directory.

## ``prod.sh``

This script is designed to update specific key-value pairs in an environment file based on predefined values.
It facilitates the management of environment configurations by automating the process of modifying key-value pairs.

Currently, it changes this key value pair:

| Key            | Value    |
|----------------|----------|
| HTTPS_REDIRECT | redirect |

### Usage

| Argument       | Description               |
|----------------|---------------------------|
| ``--env-path`` | Path of the ``.env`` file |

```bash
sh ./prod.sh --env-path 
```

### `.env` file:

```env
# Customer directoy created in /home
# Linux user created with the same name
# Docker container created with the same name
USERNAME=equal.local

# Applications credentials used for eQual, database and eQualPress
APP_USERNAME=root
APP_PASSWORD=test

# CIPHER KEY for eQual config encryption safety
CIPHER_KEY=xxxxxxxxxxxxxx

# Nginx configuration
HTTPS_REDIRECT=noredirect

# Below are the variables that are used for an eQualPress installation
# Wordpress version
WP_VERSION=6.4

# Wordpress admin email
WP_EMAIL=root@equal.local

# WordPress site title
WP_TITLE=eQualpress
```