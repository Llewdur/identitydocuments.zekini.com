#!/bin/bash

clear

composer dump-autoload &&

php artisan config:clear &&
php artisan route:clear &&
# php artisan optimize:clear && Dont clear cache as Oauth tokens needed for Xero are stored there

git pull &&
git merge origin/master &&
# git merge origin/stage &&
# git merge origin/test &&
# git merge origin/development &&

composer install --no-interaction --prefer-dist --optimize-autoloader &&

# php artisan responsecache:clear &&
php artisan route:list &&

# php artisan l5-swagger:generate &&

vendor/bin/ecs check --fix &&
vendor/bin/psalm && 
./vendor/bin/phpstan analyse --memory-limit 512M --xdebug &&
php artisan test -vvv &&

# composer test &&
    
git status
