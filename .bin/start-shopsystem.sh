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
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:static-content:deploy -f
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento deploy:mode:set developer
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento indexer:reindex
docker exec -it ${MAGENTO_CONTAINER_NAME} rm -rf var/cache var/generation var/pagecache var/di
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:di:compile
docker exec -it ${MAGENTO_CONTAINER_NAME} composer require wirecard/magento2-ee
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:upgrade
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:di:compile
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento module:status
docker exec -it ${MAGENTO_CONTAINER_NAME} bash -c "cd ~/ && chmod -R 777 /var/www/html && ls && find /root -name configure_payment_method_db.php"

#echo "Executing configure file. ->"
#docker exec ${MAGENTO_CONTAINER_NAME} php tests/_data/configure_payment_method_db.php creditcard