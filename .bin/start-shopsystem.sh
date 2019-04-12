#!/bin/bash
set -e

export MAGENTO_CONTAINER_NAME=web

docker-compose build --build-arg GATEWAY=${GATEWAY} web
docker-compose up > /dev/null &

while ! $(curl --output /dev/null --silent --head --fail "${NGROK_URL}"); do
    echo "Waiting for docker container to initialize"
    sleep 5
done

docker exec -it ${MAGENTO_CONTAINER_NAME} install-magento
docker exec -it ${MAGENTO_CONTAINER_NAME} install-sampledata
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:di:compile
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:static-content:deploy -f
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento cache:flush
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento indexer:reindex

docker exec -it ${MAGENTO_CONTAINER_NAME} composer require wirecard/magento2-ee
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:upgrade
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:di:compile
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento module:status

docker exec -it ${MAGENTO_CONTAINER_NAME} bash -c "cd ~/ && chmod -R 777 ./ && find . -name "_data" -print && find . -name "gateway_configs" -print && find . -name "configure_payment_method_db.php" -print"