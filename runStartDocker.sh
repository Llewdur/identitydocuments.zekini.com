#!/bin/bash

cd laradock/
sudo docker-compose up -d nginx redis workspace php-worker
sudo docker-compose ps
cd ..
