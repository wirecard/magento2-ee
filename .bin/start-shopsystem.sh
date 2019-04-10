#!/bin/bash
set -e # Exit with nonzero exit code if anything fails

docker-compose build --build-arg GATEWAY=${GATEWAY} web
docker-compose up > /dev/null &

while ! $(curl --output /dev/null --silent --head --fail "${NGROK_URL}"); do
    echo "Waiting for docker container to initialize"
    sleep 5
done

docker exec -it web install-magento
docker exec -it web install-sampledata