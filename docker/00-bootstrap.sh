#!/bin/bash


cd "$CRISP_WORKDIR" || exit 1


if [ -z "$SKIP_COMPOSER" ]; then
  /usr/local/bin/composer install
fi

if [ ! -f .env ]
then
  echo "# Additional environment variables below" > .env || exit 1
fi


sed -i -e "s/# $LANG UTF-8/$LANG UTF-8/" /etc/locale.gen
dpkg-reconfigure --frontend=noninteractive locales
update-locale LANG="$LANG"


cd "$CRISP_WORKDIR" || exit 1
touch .env

if [[ -z "${ASSETS_S3_BUCKET}" ]]; then
  echo "Not deploying to S3"
else
  crisp assets --deploy-to-s3
fi


chown 33:33 /tmp/crisp-dir -R