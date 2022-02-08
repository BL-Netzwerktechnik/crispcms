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


cd /var/www/crisp || exit 1

echo "Migrating..."
php bin/cli.php migrate || (echo "Failed to Migrate" && exit 1)
echo "Installing Theme..."
php bin/cli.php theme install "$CRISP_THEME" || (echo "Failed to install theme" && exit 1)
echo "Reloading theme..."
php bin/cli.php theme reload "$CRISP_THEME" overwrite || (echo "Failed to reload theme" && exit 1)
echo "Migrating theme..."
php bin/cli.php theme migrate "$CRISP_THEME" || (echo "Failed to migrate theme" && exit 1)
echo "Clearing cache..."
php bin/cli.php cache clear || (echo "Failed to clear cache" && exit 1)

echo "Setting System Timezone..."
ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
echo "Setting PHP Timezone..."
printf "[Date]\ndate.timezone = \"$TZ\"\n" > /usr/local/etc/php/conf.d/timezone.ini

echo "Chowning cache..."
rm /var/www/crisp/jrbit/cache -R
mkdir /var/www/crisp/jrbit/cache
chown 33:33 /var/www/crisp/jrbit/cache -R
rm -R /tmp/symfony-cache

cd / || exit 1

php-fpm -F -R -D || exit 1
nginx -c /etc/nginx/nginx.conf -g "daemon off;"
