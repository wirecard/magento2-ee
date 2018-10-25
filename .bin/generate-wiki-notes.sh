#!/bin/bash

git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

git clone https://$GITHUB_TOKEN@github.com/$TRAVIS_REPO_SLUG.wiki
composer make-wiki-notes

cd $REPO_NAME
git add Home.md
git commit -m "BOT: Update shop plugin versions"
git push https://$GITHUB_TOKEN@github.com/$TRAVIS_REPO_SLUG.wiki
echo "Successfully updated wiki pages"