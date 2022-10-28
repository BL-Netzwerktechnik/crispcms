#!/bin/bash


cd "$CRISP_WORKDIR" || exit 1


if [ -z "$SKIP_COMPOSER" ]; then
  /usr/local/bin/composer install
fi


echo "# We dont need me!" > .env || exit 1

cd / || exit 1

export BOOTSTRAPPED=true
