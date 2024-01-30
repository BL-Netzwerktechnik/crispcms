#!/bin/bash

if [[ -z "${MAXMIND_LICENSE}" || -z "${MAXMIND_ACCOUNT_ID}" || -z "${MAXMIND_EDITION_IDS}" ]]; then
  echo "No Maxmind credentials found, not updating geoip"
else
  echo "Updating GeoIP Database..."
  rm /etc/GeoIP.conf
  echo -e "AccountID $MAXMIND_ACCOUNT_ID\n" | sudo tee -a  /etc/GeoIP.conf
  echo -e "LicenseKey $MAXMIND_LICENSE\n" | sudo tee -a  /etc/GeoIP.conf
  echo -e "EditionIDs $MAXMIND_EDITION_IDS\n" | sudo tee -a  /etc/GeoIP.conf
  geoipupdate
fi