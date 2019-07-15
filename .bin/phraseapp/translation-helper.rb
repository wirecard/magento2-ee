##
# Shop System Plugins:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE
##

require 'logger'
require_relative 'const.rb'

# find translation keys in souce code
class TranslationHelper
  def self.get_all_keys()
    keys = []

    get_needed_files('php').each do |file_path|
      get_keys_for_file(file_path, ['__']).each do |key|
        keys.push(key)
      end
    end

    get_needed_files('phtml').each do |file_path|
      get_keys_for_file(file_path, ['__']).each do |key|
        keys.push(key)
      end
    end

    get_needed_files('html').each do |file_path|
      get_keys_for_file(file_path, ['i18n', '$t']).each do |key|
        keys.push(key)
      end
    end

    get_needed_files('js').each do |file_path|
      get_keys_for_file(file_path, ['__', '$t']).each do |key|
        keys.push(key)
      end
    end

    get_needed_files('xml').each do |file_path|
      get_keys_for_file(file_path, ['label', 'comment']).each do |key|
        keys.push(key)
      end
    end

    keys.uniq { |key| key[0] }
  end

  def self.get_keys_for_file(file_path, translation_functions)
    file_string = File.read(file_path, :encoding => 'utf-8')

    translation_keys = []

    translation_functions.each do |type|
      case type
      when '__'
        file_string.scan(/__\(\'(.*)\'(\)|\,)/).each do |key|
          translation_keys.push(key)
        end
        file_string.scan(/__\(\"(.*)\"(\)|\,)/).each do |key|
          translation_keys.push(key)
        end
      when 'i18n'
        file_string.scan(/ko i18n: \'(.*)\'/).each do |key|
          translation_keys.push(key)
        end
        file_string.scan(/ko i18n: \"(.*)\"/).each do |key|
          translation_keys.push(key)
        end
      when '$t'
        file_string.scan(/\$t\(\'(.*)\'(\)|\,)/).each do |key|
          translation_keys.push(key)
        end
        file_string.scan(/\$t\(\"(.*)\"(\)|\,)/).each do |key|
          translation_keys.push(key)
        end
      when 'label'
        file_string.scan(/\<label\>(.*)\<\/label\>/).each do |key|
          translation_keys.push(key)
        end
      when 'comment'
        file_string.scan(/\<comment\>(.*)\<\/comment\>/).each do |key|
          translation_keys.push(key)
        end
      end
    end

    translation_keys
  end

  def self.get_needed_files(file_extention)
    dirs = Const::TRANSLATION_DIRS
    files = []

    dirs.each do |dir|
      Dir.glob(dir + '/**/*.' + file_extention) do |file|
        files.push(file)
      end
    end

    files
  end
end
