#!/bin/bash

mkdir /home/$USERNAME/doc

mkdir /home/$USERNAME/doc/mkdocs
mkdir /home/$USERNAME/doc/mkdocs/docs

cp /home/$USERNAME/mkdocs.yml /home/$USERNAME/doc/mkdocs/mkdocs.yml

cd /home/$USERNAME/doc    

ln -s ./mkdocs/docs docs
ln -s ./mkdocs/mkdocs.yml mkdocs.yml

# assign ownership to user and www-data (group)
chown -R $USERNAME:www-data /home/$USERNAME/doc    
    