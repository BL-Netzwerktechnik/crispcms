#!/bin/bash

if [ -z "$BOOTSTRAPPED" ]; then
  source /opt/entrypoint.d/bootstrap.sh
fi


if [ -z "$CRISP_THEME" ]; then
  echo "Missing Environment Variable CRISP_THEME"
  exit 1
fi

if [ -z "$POSTGRES_URI" ]; then
  echo "Missing Environment Variable POSTGRES_URI"
  exit 1
fi

if [ -z "$REDIS_HOST" ]; then
  echo "Missing Environment Variable REDIS_HOST"
  exit 1
fi

if [ -z "$REDIS_PORT" ]; then
  echo "Missing Environment Variable REDIS_PORT"
  exit 1
fi

if [ -z "$REDIS_INDEX" ]; then
  echo "Missing Environment Variable REDIS_INDEX"
  exit 1
fi


if [ -z "$DEFAULT_LOCALE" ]; then
  echo "Missing Environment Variable DEFAULT_LOCALE"
  exit 1
fi


cd "$CRISP_WORKDIR" || exit 1

echo "Migrating..."
crisp-cli crisp -m || (echo "Failed to Migrate" && exit 1)
echo "Installing Theme..."
crisp-cli theme -u || (echo "Failed to install theme" && exit 1)
crisp-cli theme -i || (echo "Failed to install theme" && exit 1)
echo "Migrating theme..."
crisp-cli theme -m || (echo "Failed to migrate theme" && exit 1)
echo "Clearing cache..."
crisp-cli theme -c || (echo "Failed to clear cache" && exit 1)
echo "Executing Boot files..."
crisp-cli theme -b

echo "Setting System Timezone..."
ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
echo "Setting PHP Timezone..."
printf "[Date]\ndate.timezone = \"$TZ\"\n" > /usr/local/etc/php/conf.d/timezone.ini

echo "Chowning cache..."
rm "$CRISP_WORKDIR/jrbit/cache" -R
mkdir "$CRISP_WORKDIR/jrbit/cache"
chown 33:33 "$CRISP_WORKDIR/jrbit/cache" -R
rm /tmp/* -R

cd / || exit 1

php-fpm -F -R -D || exit 1
nginx -c /etc/nginx/nginx.conf -g "daemon off;"
