#!/bin/bash
set -e

sudo -E -u www-data crisp crisp:migration:run --no-theme
sudo -E -u www-data crisp crisp:check-permissions || true
#sudo -E -u www-data crisp crisp:theme --uninstall || true # Disabled for performance testing
sudo -E -u www-data crisp crisp:theme --install || true # Allow to fail
sudo -E -u www-data crisp crisp:theme:translations --install || true # Allow to fail
sudo -E -u www-data crisp crisp:theme:storage --install || true # Allow to fail
sudo -E -u www-data crisp crisp:migration:run --no-core || true # Allow to fail
sudo -E -u www-data crisp crisp:cache:clear
sudo -E -u www-data crisp crisp:theme:execute-boot-files || true # Allow to fail
