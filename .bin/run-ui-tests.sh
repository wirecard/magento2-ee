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

# get ngrok
curl -s https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip > ngrok.zip
unzip ngrok.zip
chmod +x $PWD/ngrok

curl -sO http://stedolan.github.io/jq/download/linux64/jq
chmod +x $PWD/jq

$PWD/ngrok authtoken ${NGROK_TOKEN}
TIMESTAMP=$(date +%s)
# start ngrok
$PWD/ngrok http 9090 -subdomain="${TIMESTAMP}-magento2-${GATEWAY}-${MAGENTO2_RELEASE_VERSION}" > /dev/null &

NGROK_URL_HTTPS=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)

# wait for ngrok to initialize
while [ ! ${NGROK_URL_HTTPS} ] || [ ${NGROK_URL_HTTPS} = 'null' ];  do
    echo "Waiting for ngrok to initialize"
    NGROK_URL_HTTPS=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)
    export NGROK_URL=$(sed 's/https/http/g' <<< "$NGROK_URL_HTTPS")
    echo $NGROK_URL
    sleep 1
done

# find out which shop extension vesion will be used for tests
# if tests triggered by PR, use extension version (branch) which originated PR
if [ ${TRAVIS_PULL_REQUEST} != "false" ]; then
    GIT_BRANCH="dev-${TRAVIS_PULL_REQUEST_BRANCH}"
# this means we want to test with latest released extension version
elif [ "${LATEST_EXTENSION_RELEASE}" == "1" ]; then
# get latest released extension version
    GIT_BRANCH="${LATEST_RELEASED_SHOP_EXTENSION_VERSION}"
# otherwise use version from current branch
else
    GIT_BRANCH="dev-${TRAVIS_BRANCH}"
fi

echo "Current shop-extension release version: ${GIT_BRANCH}"

# start shop system with plugin installed from this branch/extension version
bash .bin/start-shopsystem.sh ${GIT_BRANCH}

# find out test group to be run
if [[ $GIT_BRANCH =~ "${PATCH_RELEASE}" ]]; then
   TEST_GROUP="${PATCH_RELEASE}"
elif [[ $GIT_BRANCH =~ "${MINOR_RELEASE}" ]]; then
   TEST_GROUP="${MINOR_RELEASE}"
# run all tests in nothing else specified
else
   TEST_GROUP="${MAJOR_RELEASE}"
fi

# run UI tests
vendor/bin/codecept run acceptance -g ${TEST_GROUP} --html --xml
