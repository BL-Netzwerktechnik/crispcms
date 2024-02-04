#!/bin/bash
set -e

sudo -E -u www-data crisp --check-permissions
sudo -E -u www-data crisp --migrate
sudo -E -u www-data crisp theme --uninstall
sudo -E -u www-data crisp theme --install
sudo -E -u www-data crisp --clear-cache
sudo -E -u www-data crisp theme --migrate || true # Allow to fail
sudo -E -u www-data crisp theme --boot || true # Allow to fail

if [ ! -z "${PULL_LICENSE_ON_STARTUP}" ] || [ ! -z "${LICENSE_KEY}" ]; then
    sudo -E -u www-data crisp license --pull
fi

sudo -E -u www-data crisp --post-install

toilet "CrispCMS"
/usr/games/cowsay -f tux "... is ready to go!"
