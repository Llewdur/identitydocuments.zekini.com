#!/bin/bash

cd laradock/
sudo docker-compose up -d nginx mysql redis workspace beanstalkd php-worker
sudo docker-compose ps
cd ..
