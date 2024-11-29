#!/bin/bash

# WARNING: This script is intended to be executed by `./create.bash`

##########################
### INSTALL eQualPress ###
##########################

wget https://raw.githubusercontent.com/eQualPress/equalpress/main/install.sh -O /home/"$USERNAME"/install.sh
chmod +x /home/"$USERNAME"/install.sh
bash /home/"$USERNAME"/install.sh
rm /home/"$USERNAME"/install.sh

print "End of eQualPress installation\n"
