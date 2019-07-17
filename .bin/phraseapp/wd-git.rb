##
# Shop System Plugins:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE
##

require 'git'
require 'rainbow/refinement'
require_relative 'env.rb'

using Rainbow

class WdGit
  def initialize
    @log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')
  end

  def commit_push(repo, head, path, commit_msg)
    @log.info("Committing added/changed files under #{path}...".bright)
    git = Git.open(Dir.pwd, :log => @log)
    git.add(Dir[path])
    git.commit(commit_msg)

    @log.info("Pushing commit to branch '#{head}'...".bright)
    git.push(
      "https://#{Env::GITHUB_TOKEN}@github.com/#{repo}",
      "HEAD:refs/heads/#{head}"
    )
  rescue Git::GitExecuteError => e
    @log.warn(e)
    exit(1)
  end
end
