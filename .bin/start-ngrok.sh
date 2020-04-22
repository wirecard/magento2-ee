#!/bin/bash
# Shop System Plugins:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

set -e # Exit with nonzero exit code if anything fails

for ARGUMENT in "$@"; do
  KEY=$(echo "${ARGUMENT}" | cut -f1 -d=)
  VALUE=$(echo "${ARGUMENT}" | cut -f2 -d=)

  case "${KEY}" in
  SUBDOMAIN) SUBDOMAIN=${VALUE} ;;
  *) ;;
  esac
done

NGROK_ARCHIVE_LINK="https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip"
JQ_LINK="http://stedolan.github.io/jq/download/linux64/jq"

# download and install ngrok
curl -s "${NGROK_ARCHIVE_LINK}" >ngrok.zip
unzip ngrok.zip
chmod +x $PWD/ngrok
# Download json parser for determining ngrok tunnel
curl -sO ${JQ_LINK}
chmod +x "${PWD}"/jq

echo "SUBDOMAIN=${SUBDOMAIN}"

# Open ngrok tunnel
"${PWD}"/ngrok authtoken "${NGROK_TOKEN}"
"${PWD}"/ngrok http 80 -subdomain="${SUBDOMAIN}" >/dev/null &
NGROK_URL_HTTPS=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)

# allow ngrok to initialize
while [ ! "${NGROK_URL_HTTPS}" ] || [ "${NGROK_URL_HTTPS}" = 'null' ]; do
  echo "Waiting for ngrok to initialize"
  NGROK_URL_HTTPS=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)
  ((c++)) && ((c == 50)) && break
  sleep 1
done
