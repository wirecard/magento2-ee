#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

set -e

export VERSION=`jq .[0].release SHOPVERSIONS`


set -a # automatically export all variables from .env file
source .env
set +a

curl -s https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip > ngrok.zip
unzip ngrok.zip
chmod +x $PWD/ngrok

curl -sO http://stedolan.github.io/jq/download/linux64/jq
chmod +x $PWD/jq

$PWD/ngrok authtoken $NGROK_TOKEN
TIMESTAMP=$(date +%s)
$PWD/ngrok http 9090 -subdomain="${TIMESTAMP}-magento2-${GATEWAY}-${MAGENTO2_RELEASE_VERSION}" > /dev/null &

NGROK_URL_HTTPS=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)

while [ ! ${NGROK_URL_HTTPS} ] || [ ${NGROK_URL_HTTPS} = 'null' ];  do
    echo "Waiting for ngrok to initialize"
    NGROK_URL_HTTPS=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)
    export NGROK_URL=$(sed 's/https/http/g' <<< "$NGROK_URL_HTTPS")
    echo $NGROK_URL
    sleep 1
done

bash .bin/start-shopsystem.sh

vendor/bin/codecept run acceptance  -g "${GATEWAY}" --html --xml
