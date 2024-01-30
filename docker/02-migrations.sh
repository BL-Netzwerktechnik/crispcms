#!/bin/bash

crisp crisp --migrate
crisp theme --uninstall
crisp theme --install
crisp theme --clear-cache
crisp theme --migrate
crisp theme --boot