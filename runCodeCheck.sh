#!/bin/bash

clear

composer dump-autoload &&

git pull && 
git merge origin/master && 
git merge origin/stage && 
git merge origin/test && 
git merge origin/development &&

vendor/bin/ecs check --fix; 
vendor/bin/psalm --show-info=false; 
./vendor/bin/phpstan analyse; 

php artisan l5-swagger:generate
