#!/bin/bash
if [ -z "$1" ]
then
    echo "Please provide account name to delete (Fully Qualified Domain Name)"
else
	userdel $1   
fi
 