#!/bin/bash
set -e

php-fpm -F -R -D || exit 1
nginx -c /etc/nginx/nginx.conf -g "daemon off;"