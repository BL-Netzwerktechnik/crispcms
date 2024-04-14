#!/bin/bash
set -e

sudo -E -u www-data crisp crisp:migration:run --no-theme
sudo -E -u www-data crisp crisp:check-permissions || true
sudo -E -u www-data crisp crisp:theme --uninstall || true
sudo -E -u www-data crisp crisp:theme --install
sudo -E -u www-data crisp crisp:migration:run --no-core || true # Allow to fail
sudo -E -u www-data crisp crisp:cache:clear
sudo -E -u www-data crisp crisp:theme:execute-boot-files || true # Allow to fail

if [ ! -z "${PULL_LICENSE_ON_STARTUP}" ] || [ ! -z "${LICENSE_KEY}" ]; then
    sudo -E -u www-data crisp crisp:license:pull
fi

if [[ -z "${ASSETS_S3_BUCKET}" ]]; then
  echo "Not deploying to S3"
else
  sudo -E -u www-data crisp crisp:assets:deploy-to-s3
fi

sudo -E -u www-data crisp crisp:post-install

toilet "CrispCMS"
/usr/games/cowsay -f tux "... is ready to go!"
