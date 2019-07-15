#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

echo "Updating README badge..."
composer make-readme-badge

git add README.md
git commit -m "[skip ci] Update README badge"
git push https://$GITHUB_TOKEN@github.com/$TRAVIS_REPO_SLUG HEAD:master
echo "Successfully updated README badge"