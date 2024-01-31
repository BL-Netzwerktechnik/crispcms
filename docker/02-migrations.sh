#!/bin/bash

sudo -E -u www-data crisp --check-permissions
sudo -E -u www-data crisp --migrate
sudo -E -u www-data crisp theme --uninstall
sudo -E -u www-data crisp theme --install
sudo -E -u www-data crisp --clear-cache
sudo -E -u www-data crisp theme --migrate
sudo -E -u www-data crisp theme --boot

sudo -E -u www-data crisp --post-install

toilet "CrispCMS"
/usr/games/cowsay -f tux "... is ready to go!"