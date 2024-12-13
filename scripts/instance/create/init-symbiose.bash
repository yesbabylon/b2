#!/bin/bash

# WARNING: This script is intended to be executed by `./create.bash`

#####################
### INIT Symbiose ###
#####################

# TODO: fix packages does not exist
docker exec "$USERNAME" bash -c "
mv packages packages-core
yes | git clone -b dev-2.0 https://github.com/yesbabylon/symbiose.git packages
mv packages-core/{core,demo} packages/
rm -rf packages-core
"

printf "Clone and setup of Symbiose finished.\n"

exit 0
