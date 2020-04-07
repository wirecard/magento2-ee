#!/bin/bash
# Shop System Plugins:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

set -e # Exit with nonzero exit code if anything fails
TIMESTAMP=$(date +%s)
SHOP_SYSTEM="magento2"
NGROK_SUBDOMAIN="${RANDOM}${TIMESTAMP}-${SHOP_SYSTEM}-${MAGENTO2_VERSION}"
export NGROK_URL="http://${NGROK_SUBDOMAIN}.ngrok.io"

bash .bin/start-ngrok.sh SUBDOMAIN="${NGROK_SUBDOMAIN}"

#start shopsystem demoshop
bash .bin/start-shopsystem.sh NGROK_URL="${NGROK_URL}" \
  SHOP_VERSION="${MAGENTO2_VERSION}" \
  TRAVIS_PULL_REQUEST="${TRAVIS_PULL_REQUEST}" \
  TRAVIS_PULL_REQUEST_BRANCH="${TRAVIS_PULL_REQUEST_BRANCH}" \
  TRAVIS_BRANCH="${TRAVIS_BRANCH}" \
  IS_LATEST_EXTENSION_RELEASE="${IS_LATEST_EXTENSION_RELEASE}" \
  LATEST_RELEASED_SHOP_EXTENSION_VERSION="${LATEST_RELEASED_SHOP_EXTENSION_VERSION}"

bash .bin/run-ui-tests.sh NGROK_URL="${NGROK_URL}" \
  SHOP_SYSTEM="${SHOP_SYSTEM}" \
  SHOP_SYSTEM_CONTAINER_NAME="web" \
  SHOP_VERSION="${MAGENTO2_VERSION}" \
  GIT_BRANCH="${TRAVIS_BRANCH}" \
  TRAVIS_PULL_REQUEST="${TRAVIS_PULL_REQUEST}" \
  BROWSERSTACK_USER="${BROWSERSTACK_USER}" \
  BROWSERSTACK_ACCESS_KEY="${BROWSERSTACK_ACCESS_KEY}"
