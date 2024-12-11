#!/bin/bash

mkdir /home/$USERNAME/docs
mkdir /home/$USERNAME/docs/docs

cp /home/$USERNAME/mkdocs.yml /home/$USERNAME/docs/mkdocs.yml

# assign ownership to user and www-data (group)
chown -R $USERNAME:www-data /home/$USERNAME/docs
    