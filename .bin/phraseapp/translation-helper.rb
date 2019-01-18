require 'logger'
require_relative 'const.rb'

# find translation keys in souce code
class TranslationHelper
  def self.get_all_keys()
    keys = Array.new

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

    return keys.uniq
  end

  def self.get_keys_for_file(file_path, translation_functions)
    file = File.open(file_path, 'r')
    file_string = file.read
    file.close

    translation_keys = Array.new

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

    return translation_keys
  end

  def self.get_needed_files(file_extention)
    dirs = Const::TRANSLATION_DIRS
    files = Array.new

    dirs.each do |dir|
      Dir.glob(dir + '/**/*.' + file_extention) do |file|
        files.push(file)
      end
    end

    return files
  end
end
