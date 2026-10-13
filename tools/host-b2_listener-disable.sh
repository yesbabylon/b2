#!/bin/bash

# Stop the b2-listener service
systemctl stop b2-listener.service

# Do not start b2-listener on boot
systemctl disable b2-listener.service
