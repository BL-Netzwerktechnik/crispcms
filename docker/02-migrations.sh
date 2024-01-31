#!/bin/bash

crisp --check-permissions
crisp --migrate
crisp theme --uninstall
crisp theme --install
crisp --clear-cache
crisp theme --migrate
crisp theme --boot

crisp --post-install

toilet "CrispCMS"
/usr/games/cowsay -f tux "... is ready to go!"