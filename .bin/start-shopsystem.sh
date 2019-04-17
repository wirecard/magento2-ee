#!/bin/bash
set -e

export MAGENTO_CONTAINER_NAME=web
export MYSQL_DATABASE=magento
export MYSQL_USER=magento
export MYSQL_PASSWORD=magento

docker-compose build --build-arg GATEWAY=${GATEWAY} web
docker-compose up > /dev/null &

while ! $(curl --output /dev/null --silent --head --fail "${NGROK_URL}"); do
    echo "Waiting for docker container to initialize"
    sleep 5
done

# install magento shop
docker exec -it ${MAGENTO_CONTAINER_NAME} install-magento
docker exec -it ${MAGENTO_CONTAINER_NAME} install-sampledata

# install wirecard magento2 plugin
docker exec -it ${MAGENTO_CONTAINER_NAME} composer require wirecard/magento2-ee
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:upgrade
docker exec -it ${MAGENTO_CONTAINER_NAME} php bin/magento setup:di:compile

echo "Give permissions to load css! - It is mandatory!"
docker exec -it ${MAGENTO_CONTAINER_NAME} bash -c "chmod -R 777 ./"

docker exec --env MYSQL_DATABASE=${MYSQL_DATABASE} \
            --env MYSQL_USER=${MYSQL_USER} \
            --env MYSQL_PASSWORD=${MYSQL_PASSWORD} \
            --env GATEWAY=${GATEWAY} \
            ${MAGENTO_CONTAINER_NAME} bash -c "cd /magento2-plugin/tests/_data/ && php configure_payment_method_db.php creditcard"

# wait for payment method to be activated
sleep 7m