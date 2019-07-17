##
# Shop System Plugins:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE
##

require 'octokit'
require 'rainbow/refinement'
require_relative 'const.rb'
require_relative 'env.rb'

using Rainbow

class WdGithub
  def initialize
    @github = Octokit::Client.new(:access_token => Env::GITHUB_TOKEN)
    @log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')
  end

  def create_pr(repo, base, head, title, body)
    @log.info("Creating PR from '#{head}' to '#{base}', if it doesn't exist...".bright)
    pr = @github.create_pull_request(repo, base, head, title, body)
    @log.info("View pull request here: #{pr.html_url}")
  rescue Octokit::UnprocessableEntity
    @log.info("A pull request already exists for #{repo}:#{head}")
  rescue Octokit::Error => e
    @log.fatal('Error while creating pull request.')
    @log.debug(e)
    exit(1)
  end
end
