#!/usr/bin/env bash

#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

set -e
set -x
RELEASE_NOTES=$(curl -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/${TRAVIS_REPO_SLUG}/releases/tags/${TRAVIS_TAG} | jq -r ' .body')
while [ -z  "${RELEASE_NOTES}" ] || [ "${RELEASE_NOTES}" == 'null' ]; do
    echo "Waiting for release notes to apear"
    ((c++)) && ((c==50)) && break
    sleep 1
done
