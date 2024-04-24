# Scripts explanation

## equal.setup.bash

This script is responsible for setting up the eQual Framework environment on a server. It copies the `docker-compose.yml` file from the B2 repository to the user's home directory, replaces placeholders in the file with computed values, builds and starts the Docker containers, clones the eQual framework repository, generates the configuration file, and initializes the eQual Framework database and core package.

### Prerequisites

- Docker and Docker Compose must be installed on the server.
- The B2 repository containing the `docker-compose.yml` file must be available in the `/root` folder.

### Progress Tasks

1. **Get docker-compose.yml file:**
   - Copies the `docker-compose.yml` file from the B2 repository to the user's home directory.

2. **Replace Placeholders for Docker Compose:**
   - Replaces placeholders in the `docker-compose.yml` file with computed values such as `EQ_PORT`, `DB_HOSTNAME`, `DB_PORT`, `PMA_HOSTNAME`, and `PMA_PORT`.

3. **Building and Starting the Containers:**
   - Builds and starts the Docker containers defined in the `docker-compose.yml` file.

4. **Clone eQual Framework:**
   - Clones the eQual framework repository from the specified branch (`dev-2.0`).

5. **Generate config/config.json:**
   - Generates the `config.json` file for the eQual Framework with database and application details.

6. **Initialize eQual Framework Database and Core Package:**
   - Initializes the eQual Framework database and imports the core package.

## symbiose.setup.bash

This script initiates the cloning and setup process for the Symbiose tool within the designated environment.

### Progress Task

The script performs the following tasks:

- Clones the Symbiose repository from the dev-2.0 branch.
- Moves core and demo packages to the appropriate directory.
- Removes the outdated packages-core directory.


## prod.sh

This script is designed to update specific key-value pairs in an environment file based on predefined values. It facilitates the management of environment configurations by automating the process of modifying key-value pairs.

Currently it changes this key value pair:

| Key            | Value    |
|----------------|----------|
| HTTPS_REDIRECT | redirect |
