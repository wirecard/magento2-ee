#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

set -e
set -x

PREVIEW_LINK='https://raw.githack.com/wirecard/reports'
REPORT_FILE='report.html'
#choose slack channel depending on the gateway
if [[ ${GATEWAY} = "API-WDCEE-TEST" ]]; then
  CHANNEL='shs-ui-api-wdcee-test'
elif [[  ${GATEWAY} = "API-TEST" ]]; then
   CHANNEL='shs-ui-api-test'
elif [[  ${GATEWAY} = "NOVA" ]]; then
   CHANNEL='shs-ui-nova'
fi

#send information about the build
curl -X POST -H 'Content-type: application/json' \
    --data "{'text': 'Build Failed. Magento2 version: ${MAGENTO2_VERSION}\n
    Build URL : ${TRAVIS_JOB_WEB_URL}\n
    Build Number: ${TRAVIS_BUILD_NUMBER}\n
    Branch: ${BRANCH_FOLDER}', 'channel': '${CHANNEL}'}" ${SLACK_ROOMS}

if [[ ${COMPATIBILITY_CHECK}  == "0" ]]; then
  # send link to the report into slack chat room
  curl -X POST -H 'Content-type: application/json' --data "{
      'attachments': [
          {
              'fallback': 'Failed test data',
              'text': 'There are failed tests.
               Test report: ${PREVIEW_LINK}/${SCREENSHOT_COMMIT_HASH}/${RELATIVE_REPORTS_LOCATION}/${REPORT_FILE} .
               All screenshots can be found  ${REPO_LINK}/tree/${SCREENSHOT_COMMIT_HASH}/${RELATIVE_REPORTS_LOCATION} .',
              'color': '#764FA5'
          }
      ], 'channel': '${CHANNEL}'
  }"  ${SLACK_ROOMS};
else
  # send link to the report into slack chat room if we are not compatible with the latest released version
  curl -X POST -H 'Content-type: application/json' --data "{
      'attachments': [
          {
              'fallback': 'Failed test data',
              'text': 'We are not compatible with the latest Magento2 released version: ${MAGENTO2_VERSION}.
               Test report: ${PREVIEW_LINK}/${SCREENSHOT_COMMIT_HASH}/${RELATIVE_REPORTS_LOCATION}/${REPORT_FILE} .
               All screenshots can be found  ${REPO_LINK}/tree/${SCREENSHOT_COMMIT_HASH}/${RELATIVE_REPORTS_LOCATION} .',
              'color': '#764FA5'
          }
      ], 'channel': '${CHANNEL}'
  }"  ${SLACK_ROOMS};
fi
