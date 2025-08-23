#!/bin/bash
cd /home/site/wwwroot
php artisan config:clear
php artisan route:clear
php artisan view:clear
cp nginx_custom.conf /etc/nginx/sites-enabled/default
service nginx reload
