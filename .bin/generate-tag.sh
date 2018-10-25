#!/bin/bash

if [[ $TRAVIS_BRANCH == 'master' ]]; then
    if [[ $TRAVIS_PULL_REQUEST == 'false' ]]; then
        VERSION=`cat SHOPVERSIONS | jq -r '.[0] | .release'`
        STATUS=`curl -s -o /dev/null -w "%{http_code}" -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/${TRAVIS_REPO_SLUG}/git/refs/tags/${VERSION}`

        if [[ ${STATUS} == "200" ]] ; then
            echo "Tag is up to date with version."
            exit 0
        elif [[ ${STATUS} != "404" ]] ; then
            echo "Got status ${STATUS} from GitHub. Exiting."
            exit 0
        else
            echo "Version is updated, creating tag ${VERSION}"
        fi

        git config --global user.name "Travis CI"
        git config --global user.email "wirecard@travis-ci.org"

        git tag -a ${VERSION} -m "Pre-release version"
        git push --quiet https://$GITHUB_TOKEN@github.com/$TRAVIS_REPO_SLUG $VERSION > /dev/null 2>&1
    fi
fi
