#!/bin/bash
set -e # Exit with nonzero exit code if anything fails

export MAGENTO_CONTAINER_NAME=web

docker-compose build --build-arg GATEWAY=${GATEWAY} web
docker-compose up > /dev/null &

while ! $(curl --output /dev/null --silent --head --fail "${NGROK_URL}"); do
    echo "Waiting for docker container to initialize"
    sleep 5
done

docker exec -it ${MAGENTO_CONTAINER_NAME} install-magento
docker exec -it ${MAGENTO_CONTAINER_NAME} install-sampledata
docker exec -it ${MAGENTO_CONTAINER_NAME} composer require wirecard/magento2-ee
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:upgrade
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:di:compile
echo "START LAST STEP"
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento module:status
docker exec -it ${MAGENTO_CONTAINER_NAME} bash -c "cd ~/ && chmod -R 777 /var/www/html && ls"