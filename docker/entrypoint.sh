#!/bin/bash

if [ -z "$BOOTSTRAPPED" ]; then
  source /opt/entrypoint.d/bootstrap.sh
fi

if [ -z "$POSTGRES_URI" ]; then
  echo "Missing Environment Variable POSTGRES_URI"
  exit 1
fi

if [ -z "$DEFAULT_LOCALE" ]; then
  echo "Missing Environment Variable DEFAULT_LOCALE"
  exit 1
fi

sudo sed -i -e "s/# $LANG UTF-8/$LANG UTF-8/" /etc/locale.gen
sudo dpkg-reconfigure --frontend=noninteractive locales
sudo update-locale LANG="$LANG"


cd "$CRISP_WORKDIR" || exit 1
touch .env


if [[ -z "${ASSETS_S3_BUCKET}" ]]; then
  echo "Not deploying to S3"
else
  crisp assets --deploy-to-s3
fi

if [[ -z "${MAXMIND_LICENSE}" || -z "${MAXMIND_ACCOUNT_ID}" || -z "${MAXMIND_EDITION_IDS}" ]]; then
  echo "No Maxmind credentials found, not updating geoip"
else
  echo "Updating GeoIP Database..."
  sudo rm /etc/GeoIP.conf
  echo -e "AccountID $MAXMIND_ACCOUNT_ID\n" | sudo tee -a  /etc/GeoIP.conf
  echo -e "LicenseKey $MAXMIND_LICENSE\n" | sudo tee -a  /etc/GeoIP.conf
  echo -e "EditionIDs $MAXMIND_EDITION_IDS\n" | sudo tee -a  /etc/GeoIP.conf
  sudo geoipupdate
fi

crisp crisp --migrate
crisp theme --uninstall
crisp theme --install
crisp theme --clear-cache
crisp theme --migrate
crisp theme --boot

cd / || exit 1

php-fpm -F -R -D || exit 1
crisp crisp -p

sudo rm /tmp/crisp-cache -rf

sudo nginx -c /etc/nginx/nginx.conf -g "daemon off;"
