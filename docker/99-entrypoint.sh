#!/bin/bash
set -e

toilet "CrispCMS"
/usr/games/cowsay -f tux "... is ready to go!"


php-fpm -F -R -D || exit 1
cron
nginx -c /etc/nginx/nginx.conf -g "daemon off;"