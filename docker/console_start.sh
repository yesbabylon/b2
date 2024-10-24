#!/bin/bash
sudo docker stop portainer && sudo docker rm portainer
# sudo docker stop netdata && sudo docker rm netdata
sudo docker run --name portainer -d -p 9000:9000 -v /var/run/docker.sock:/var/run/docker.sock -v portainer_data:/data portainer/portainer-ce:latest
# sudo docker run -d --name=netdata \
#  -p 19999:19999 \
#  -v /proc:/host/proc:ro \
#  -v /sys:/host/sys:ro \
#  -v /var/run/docker.sock:/var/run/docker.sock:ro \
#  --cap-add SYS_PTRACE \
#  --security-opt apparmor=unconfined \
#  netdata/netdata