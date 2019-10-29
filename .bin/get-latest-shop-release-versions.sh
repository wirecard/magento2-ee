#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

set -e
set -x

curl -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/magento/magento2/releases | jq -r '.[] | .tag_name' | egrep -v [a-zA-Z] | head -n3 > tmp.txt
# sort versions in descending order
sort -nr tmp.txt > ${MAGENTO2_COMPATIBILITY_FILE}

if [[ $(git diff HEAD ${MAGENTO2_COMPATIBILITY_FILE}) != '' ]]; then
    git config --global user.name "Travis CI"
    git config --global user.email "wirecard@travis-ci.org"

    git add  ${MAGENTO2_COMPATIBILITY_FILE}
    git commit -m "${SHOP_SYSTEM_UPDATE_COMMIT}"
    git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:master
fi
