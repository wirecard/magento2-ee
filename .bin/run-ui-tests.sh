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

#setup codeception and dependencies
rm -rf composer.lock

#get shopsystem-ui-testsuite project
git clone  --branch TPWDCEE-6288-a51try-configuration https://github.com/wirecard/shopsystems-ui-testsuite.git
cd shopsystems-ui-testsuite
echo "Installing shopsystems-ui-testsuite dependencies"
docker run --rm -it --volume $(pwd):/app prooph/composer:7.2 install --dev

export SHOP_SYSTEM=${SHOP_SYSTEM}
export SHOP_URL="${NGROK_URL}"
export EXTENSION_VERSION="${GIT_BRANCH}"
export DB_HOST="${MAGENTO2_DB_HOST%%:*}"
export DB_NAME="${MAGENTO2_DB_NAME}"
export DB_USER="${MAGENTO2_DB_USER}"
export DB_PORT="${MAGENTO2_DB_HOST#*:}"
export DB_PASSWORD="${MAGENTO2_DB_PASSWORD}"
export SHOP_VERSION="${SHOP_VERSION}"
export BROWSERSTACK_USER="${BROWSERSTACK_USER}"
export BROWSERSTACK_ACCESS_KEY="${BROWSERSTACK_ACCESS_KEY}"

echo "Running tests"
vendor/bin/codecept run acceptance \
  -g "${TEST_GROUP}" -g "${SHOP_SYSTEM}" \
  --env ci_magento2 --html --xml --debug