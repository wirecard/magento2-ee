#!/bin/bash
# Shop System Plugins:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE
set -e # Exit with nonzero exit code if anything fails

set -a
source .env
set +a

for ARGUMENT in "$@"; do
  KEY=$(echo "${ARGUMENT}" | cut -f1 -d=)
  VALUE=$(echo "${ARGUMENT}" | cut -f2 -d=)

  case "${KEY}" in
  NGROK_URL) NGROK_URL=${VALUE} ;;
  GIT_BRANCH) GIT_BRANCH=${VALUE} ;;
  TRAVIS_PULL_REQUEST) TRAVIS_PULL_REQUEST=${VALUE} ;;
  SHOP_SYSTEM) SHOP_SYSTEM=${VALUE} ;;
  SHOP_VERSION) SHOP_VERSION=${VALUE} ;;
  BROWSERSTACK_USER) BROWSERSTACK_USER=${VALUE} ;;
  BROWSERSTACK_ACCESS_KEY) BROWSERSTACK_ACCESS_KEY=${VALUE} ;;
  *) ;;
  esac
done

# if tests triggered by PR, use different Travis variable to get branch name
if [ "${TRAVIS_PULL_REQUEST}" != "false" ]; then
  export GIT_BRANCH="${TRAVIS_PULL_REQUEST_BRANCH}"
fi

# find out test group to be run
if [[ $GIT_BRANCH =~ ${PATCH_RELEASE} ]]; then
  TEST_GROUP="${PATCH_RELEASE}"
elif [[ $GIT_BRANCH =~ ${MINOR_RELEASE} ]]; then
  TEST_GROUP="${MINOR_RELEASE}"
# run all tests in nothing else specified
else
  TEST_GROUP="${MAJOR_RELEASE}"
fi

rm -rf composer.lock
rm -rf composer.json

#get shopsystem-ui-testsuite project
#composer global config minimum-stability dev
composer require wirecard/shopsystem-ui-testsuite:dev-TWDCEE-6288-configuration

docker-compose run \
  -e SHOP_SYSTEM="${SHOP_SYSTEM}" \
  -e SHOP_URL="${NGROK_URL}" \
  -e SHOP_VERSION="${SHOP_VERSION}" \
  -e EXTENSION_VERSION="${GIT_BRANCH}" \
  -e DB_HOST="${MYSQL_HOST}" \
  -e DB_NAME="${MYSQL_DATABASE}" \
  -e DB_USER="${MYSQL_USER}" \
  -e DB_PASSWORD="${MYSQL_PASSWORD}" \
  -e BROWSERSTACK_USER="${BROWSERSTACK_USER}" \
  -e BROWSERSTACK_ACCESS_KEY="${BROWSERSTACK_ACCESS_KEY}" \
  codecept run acceptance \
  -g "${TEST_GROUP}" -g "${SHOP_SYSTEM}" \
  --env ci --html --xml --debug