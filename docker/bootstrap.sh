#!/bin/bash


cd "$CRISP_WORKDIR" || exit 1


if [ -z "$SKIP_COMPOSER" ]; then
  /usr/local/bin/composer install
fi

if [ ! -f .env ]
then
  echo "# Additional environment variables below" > .env || exit 1
fi


cd / || exit 1

export BOOTSTRAPPED=true
