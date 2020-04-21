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
export SHOP_VERSION=${SHOP_VERSION}
export WIRECARD_PLUGIN_VERSION=${EXTENSION_VERSION}

export PHP_VERSION=71
export MAGENTO2_CONTAINER_NAME=web

pip3 install xonsh
git clone https://"${WIRECARD_CEE_GITHUB_TOKEN}":@github.com/wirecard-cee/docker-images.git
cd docker-images/magento2-dev
#run shop system in the background
nohup ./run.xsh ${MAGENTO2_CONTAINER_NAME} --daemon &>/dev/null

sleep 10

# wait till shop is up
while [[ $(docker exec -ti ${MAGENTO2_CONTAINER_NAME} supervisorctl status | grep magento2) != *"EXITED"* ]]; do
    echo "Waiting for docker container to initialize"
    ((c++)) && ((c == 100)) && break
    sleep 5
done
sleep 15
#change hostname
docker exec -ti ${MAGENTO2_CONTAINER_NAME}  /opt/wirecard/apps/magento2/bin/hostname-changed.xsh ${NGROK_URL#*//}

#set cron to every minute
docker exec -ti ${MAGENTO2_CONTAINER_NAME} /bin/sh -c "sed 's/15/1/g' /srv/http/vendor/wirecard/magento2-ee/etc/crontab.xml > /srv/http/vendor/wirecard/magento2-ee/etc/crontab1.xml"
docker exec -ti ${MAGENTO2_CONTAINER_NAME} /bin/sh -c "cp /srv/http/vendor/wirecard/magento2-ee/etc/crontab1.xml /srv/http/vendor/wirecard/magento2-ee/etc/crontab.xml"

# disable config cache
docker exec -ti ${MAGENTO2_CONTAINER_NAME} php /srv/http/bin/magento cache:disable config

