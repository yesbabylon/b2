#!/bin/bash
cd packages
git pull
cd ..
git pull
./equal.run --do=init_package --package=core --force=true
./equal.run --do=init_package --package=identity --force=true
./equal.run --do=init_package --package=sale --force=true
./equal.run --do=init_package --package=finance --force=true
./equal.run --do=init_package --package=hr --force=true
./equal.run --do=init_package --package=communication --force=true
./equal.run --do=init_package --package=purchase --force=true
./equal.run --do=init_package --package=documents --force=true
./equal.run --do=init_package --package=realestate --force=true
./equal.run --do=init_package --package=infra --force=true
./equal.run --do=init_package --package=fmt --force=true
./equal.run --do=init_app --app=app --package=fmt --force=true
./equal.run --do=init_app --app=portal --package=fmt --force=true
