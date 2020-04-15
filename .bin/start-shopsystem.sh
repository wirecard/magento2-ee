#!/bin/bash
# Shop System Plugins:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

set -e
set -a
source .env

for ARGUMENT in "$@"; do
  KEY=$(echo "${ARGUMENT}" | cut -f1 -d=)
  VALUE=$(echo "${ARGUMENT}" | cut -f2 -d=)

  case "${KEY}" in
  NGROK_URL) NGROK_URL=${VALUE} ;;
  SHOP_VERSION) SHOP_VERSION=${VALUE} ;;
  TRAVIS_PULL_REQUEST) TRAVIS_PULL_REQUEST="${VALUE}" ;;
  TRAVIS_PULL_REQUEST_BRANCH) TRAVIS_PULL_REQUEST_BRANCH="${VALUE}" ;;
  TRAVIS_BRANCH) TRAVIS_BRANCH="${VALUE}" ;;
  USE_SPECIFIC_EXTENSION_RELEASE) USE_SPECIFIC_EXTENSION_RELEASE=${VALUE} ;;
  SPECIFIC_RELEASED_SHOP_EXTENSION_VERSION) SPECIFIC_RELEASED_SHOP_EXTENSION_VERSION=${VALUE} ;;
  *) ;;
  esac
done

EXTENSION_VERSION="master"
# find out which shop extension vesion will be used for tests
# if tests triggered by PR, use extension version (branch) which originated PR
if [ "${TRAVIS_PULL_REQUEST}" != "false" ]; then
    EXTENSION_VERSION="${TRAVIS_PULL_REQUEST_BRANCH}"
# this means we want to test with latest released extension version
elif [ "${USE_SPECIFIC_EXTENSION_RELEASE}" == "1" ]; then
# get latest released extension version
    EXTENSION_VERSION="${SPECIFIC_RELEASED_SHOP_EXTENSION_VERSION}"
# otherwise use version from current branch
else
    EXTENSION_VERSION="${TRAVIS_BRANCH}"
fi

export MAGENTO2_CONTAINER_NAME=web

export PHP_VERSION=71
export SHOP_VERSION=${SHOP_VERSION}
export WIRECARD_PLUGIN_VERSION=${EXTENSION_VERSION}

git clone https://github.com/wirecard-cee/xc.git
cd https://github.com/wirecard-cee/docker-images/magento2-dev
./run.xsh ${MAGENTO2_CONTAINER_NAME} >/dev/null &


#somehow wait till shop is up
echo "NGROK_URL = $NGROK_URL"
while ! $(curl --output /dev/null --silent --head --fail "${NGROK_URL}"); do
    echo "Waiting for docker container to initialize"
    ((c++)) && ((c == 50)) && break
    sleep 5
done

#change hostname
docker exec -ti ${MAGENTO2_CONTAINER_NAME}  /opt/wirecard/apps/magento2/bin/hostname-changed.xsh ${NGROK_URL#*//}

#set cron to every minute
docker exec -ti ${MAGENTO2_CONTAINER_NAME} "sed 's/15/1/g' /srv/http/vendor/wirecard/magento2-ee/etc/crontab.xml > /srv/http/vendor/wirecard/magento2-ee/etc/crontab.xml"

# disable config cache
docker exec -ti ${MAGENTO2_CONTAINER_NAME} php /srv/http/bin/magento cache:disable config

