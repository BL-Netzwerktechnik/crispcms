#!/bin/bash

php-fpm -F -R -D || exit 1
crisp crisp -p
nginx -c /etc/nginx/nginx.conf -g "daemon off;"