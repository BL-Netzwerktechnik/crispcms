#!/bin/bash
set -e


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
touch .env | true

echo "SHELL=/bin/bash" > /etc/cron.d/crontab

while IFS='=' read -r name value ; do
    printf 'export %s=%q\n' "$name" "$value"
done < <(printenv) >> /etc/.env.sh

chmod +x /etc/.env.sh

echo "* * * * * www-data /bin/bash -c 'source /etc/.env.sh && cd /var/www/crisp && /usr/local/bin/crisp cron --run' >> /var/log/crisp/cron.log 2>&1" >> /etc/cron.d/crontab
