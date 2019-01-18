module Const
  GITHUB_PHRASEAPP_PR_TITLE = '[PhraseApp] Update locales'.freeze
  GITHUB_PHRASEAPP_PR_BODY = 'Update locales from PhraseApp'.freeze
  GIT_PHRASEAPP_COMMIT_MSG = '[skip ci] Update translations from PhraseApp'.freeze
  GIT_PHRASEAPP_BRANCH_BASE = 'master'.freeze
  PHRASEAPP_PROJECT_ID = '9036e89959d471e0c2543431713b7ba1'.freeze
  PHRASEAPP_FALLBACK_LOCALE = 'en_US'.freeze

  # project-specific mappings for locales to filenames
  PHRASEAPP_TAG = 'magento2'.freeze
  LOCALE_SPECIFIC_MAP = {
    'zh_TW': 'zh_Hant_TW',
    'zh_CN': 'zh_Hans_CN',
  }.freeze

  # paths relative to project root
  PLUGIN_I18N_DIR = 'i18n'.freeze

  # relevant directories for translations
  TRANSLATION_DIRS = ['Block', 'Controller', 'Gateway', 'Model', 'Observer', 'etc', 'view'].freeze
end
