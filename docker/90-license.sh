if [ ! -z "${LICENSE_KEY}" ]; then
    sudo -E -u www-data crisp crisp:license:pull
fi