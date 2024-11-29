#!/bin/bash

# Stop portainer and remove it
docker stop portainer && sudo docker rm portainer

# Start portainer container
docker run --name portainer -d -p 9000:9000 -v /var/run/docker.sock:/var/run/docker.sock -v portainer_data:/data portainer/portainer-ce:latest
