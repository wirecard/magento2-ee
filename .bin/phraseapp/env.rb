##
# Shop System Plugins:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE
##

module Env
  DEBUG = ENV['DEBUG'] == '1'

  GITHUB_TOKEN = (ENV['GITHUB_TOKEN'] || '').freeze
  PHRASEAPP_PULL = (ENV['PHRASEAPP_PULL'] || '').freeze
  PHRASEAPP_TOKEN = (ENV['PHRASEAPP_TOKEN'] || '').freeze
  TRAVIS_BRANCH = (ENV['TRAVIS_BRANCH'] || '').freeze
  TRAVIS_REPO_SLUG = (ENV['TRAVIS_REPO_SLUG'] || '').freeze
end
