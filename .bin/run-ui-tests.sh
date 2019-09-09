#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE


set -e
set -a # automatically export all variables from .env file
source .env
set +a

export LATEST_RELEASED_SHOP_EXTENSION_VERSION=`jq .[0].release SHOPVERSIONS`

curl -s https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip > ngrok.zip
unzip ngrok.zip
chmod +x $PWD/ngrok

curl -sO http://stedolan.github.io/jq/download/linux64/jq
chmod +x $PWD/jq

$PWD/ngrok authtoken ${NGROK_TOKEN}
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

#if tests triggered by
GIT_BRANCH="dev-$(git branch | grep \* | cut -d ' ' -f2)"
# if tests triggered by PR, use extension version (branch) which originated PR
if [ ${TRAVIS_PULL_REQUEST} != "false" ]; then
  GIT_BRANCH="dev-${TRAVIS_PULL_REQUEST_BRANCH}"
fi

# this means we want to test with latest released extension version
if [ "${LATEST_EXTENSION_RELEASE}" == "1" ]; then
# get latest released extension version
    GIT_BRANCH="${LATEST_RELEASED_SHOP_EXTENSION_VERSION}"
fi

echo "Current git branch ${GIT_BRANCH}"
echo "LATEST_EXTENSION_RELEASE variable value: ${LATEST_EXTENSION_RELEASE}"
# THIS IS FOR TEST PURPOSES! TO BE REMOVED
GIT_BRANCH="dev-master"

#start shop system with plugin installed from this branch/extension version
bash .bin/start-shopsystem.sh ${GIT_BRANCH}

TEST_GROUP=''

if [[ $GIT_BRANCH =~ "${BATCH_RELEASE}" ]]
then
   TEST_GROUP="${BATCH_RELEASE}"
elif [[ $GIT_BRANCH =~ "${MINOR_RELEASE}" ]]
then
   TEST_GROUP="${MINOR_RELEASE}"
#run all tests in nothing else specified
else
   TEST_GROUP=''
fi

vendor/bin/codecept run acceptance -g ${TEST_GROUP} --html --xml
