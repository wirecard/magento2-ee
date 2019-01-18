require 'rainbow/refinement'
require_relative '.bin/phraseapp/wd-phraseapp.rb'
require_relative '.bin/phraseapp/wd-project.rb'

using Rainbow

#-------------------------------------------------------------------------------
# PhraseApp tasks
#-------------------------------------------------------------------------------
namespace :phraseapp do
  desc 'Pull locale files'
  task :pull do
    WdPhraseApp.new.pull_locales
  end

  desc 'Parse translatable keys and push to a PhraseApp branch'
  task :push do
    if WdProject.new.worktree_has_key_changes?
      WdPhraseApp.new.push_to_branch
    end
  end

  desc '[CI] Pull locales, commit & push to git remote'
  task :ci_update do
    WdPhraseApp.new.pull_locales && WdProject.new.commit_push_pr_locales
  end

  desc '[CI] Check if PhraseApp is up to date with the project'
  task :ci_check_if_in_sync do
    unless WdPhraseApp.new.is_in_sync?
      puts 'PhraseApp is not in sync with the current commit. Exiting.'.red.bright
      exit(1)
    end
  end
end
